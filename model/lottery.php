<?php

/**
 * Class ModelLikbetLottery
 *
 * Class to work with the winners of lottery
 *
 * @author Dima Dzyubenko
 * @version 2.0
 */
class ModelLikbetLottery extends Model
{
    /**
     * Получить результаты розыгрышей
     *
     * @param array $data Массив параметров
     * @param bool $just_count Надо ли считать кол-во записей или отдавать их
     * @return array Массив отсортированных записей
     */
    function getLottery($data, $just_count = false)
    {
        $data['is_logged'] = $this->customer->isLogged();
        if ($just_count) {
            $cached = $this->cache->get('lottery_cnt_' . md5(serialize($data)));
            if (is_numeric($cached)) {
                return $cached;
            }
            $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "likbet_lottery`";
        } else {
            $cached = $this->cache->get('lottery_' . md5(serialize($data)));
            if (is_array($cached)) {
                return $cached;
            }
            $sql = "SELECT * FROM `" . DB_PREFIX . "likbet_lottery`";
        }

        $where = [];
        $params = [];

        if (!empty($data['lot_id'])) {
            $where[] = "lot_id=?";
            $params[] = $data['lot_id'];
        }

        if (!empty($data['winner'])) {
            $where[] = "winner=?";
            $params[] = $data['winner'];
        }

        if (count($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        if (!$just_count) {
            $sort_data = array(
                'date_played',
                'lot_id',
                'prize',
                'winner',
                'customers',
                'percent',
            );

            if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
                $sql .= " ORDER BY `" . $data['sort'] . "`";
            } else {
                $sql .= " ORDER BY date_played";
            }

            if (isset($data['order']) && (strtolower($data['order']) == 'desc')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (!$data['no_limit']) {
                $start = 0;
                $limit = 20;
                if (isset($data['start'])) {
                    $start = ($data['start'] < 0) ? 0 : $data['start'];
                }

                if (isset($data['limit'])) {
                    $limit = ($data['limit'] < 1) ? 20 : $data['limit'];
                }
                $sql .= " LIMIT " . (int)$start . ',' . (int)$limit;
            }
        }

        $query = $this->db->query($sql, $params);

        if ($just_count) {
            $this->cache->set('lottery_cnt_' . md5(serialize($data)), (int)$query->row['total'], 300, ['lots']);
            return $query->row['total'];
        } else {
            $this->load->model('account/customer');
            $this->load->model('likbet/lot');
            $this->load->language('likbet/lottery');

            foreach ($query->rows as &$row) {
                // получить информацию о победителе
                $row['customer_info'] = $this->model_account_customer->getCustomer($row['winner']);
                $row['customer_info']['avatars'] = $this->model_account_customer->getCustomerAvatars($row['winner']);
                // получить информацию о лоте
                $row['lot_info'] = $this->model_likbet_lot->getArchivedLot($row['lot_id']);
                if ($row['lot_info']['obj_type'] == 'money' || $row['lot_info']['obj_type'] == 'super') {
                    $row['lot_info']['text_win_sum_or_name'] = sprintf('%s %s',
                        $this->language->get('win_sum'),
                        $this->currency->format($row['prize'], $this->config->get('config_currency'))
                    );
                    $row['lot_info']['text_for_money_and_super'] = sprintf('%s %s',
                        $this->language->get('in_lottery'),
                        $this->currency->format($row['lot_info']['original_price'], $this->config->get('config_currency'))
                    );
                } else if ($row['lot_info']['obj_type'] == 'product' || $row['lot_info']['obj_type'] == 'exclusive') {
                    $row['lot_info']['text_win_sum_or_name'] = $row['lot_info']['name'];
                }
                $row['lot_info']['detail_url'] = $this->url->link('lot/details', 'lot_id=' . $row['lot_id']);
                // CSS класс
                if ($row['lot_info']['obj_type'] == 'money') {
                    $row['lot_info']['icon'] = 'icon-home-win-hr';
                } else if ($row['lot_info']['obj_type'] == 'super') {
                    $row['lot_info']['icon'] = 'icon-home-win-hr-jp';
                } else if ($row['lot_info']['obj_type'] == 'product') {
                    $row['lot_info']['icon'] = 'icon-home-win-product';
                } else if ($row['lot_info']['obj_type'] == 'exclusive') {
                    $row['lot_info']['icon'] = 'icon-home-win-exclusive-jp';
                }

                $this->load->model('likbet/date');
                $row['format_time'] = $this->model_likbet_date->DateFormat($row['date_played']);
            }
            $this->cache->set('lottery_' . md5(serialize($data)), $query->rows, 300, ['lots']);

            return $query->rows;
        }
    }

    /**
     * Получить выигрыши пользователя
     *
     * @param array $data Массив параметров
     * @param bool $just_count Надо ли считать кол-во записей или отдавать их
     * @return mixed Массив отфильтрованных и отсортированных записей либо счетчик записей
     */
    public function getMyWins($data = array(), $just_count = false)
    {
        if ($just_count) {
            $cached = $this->cache->get('wins_cnt_' . md5(serialize($data)));
            if (is_numeric($cached)) {
                return $cached;
            }
            $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "likbet_lottery l ";
        } else {
            $cached = $this->cache->get('wins_' . md5(serialize($data)));
            if (is_array($cached)) {
                return $cached;
            }
            $sql = "SELECT l.*, la.* FROM " . DB_PREFIX . "likbet_lottery l ";
        }
        $sql .= " LEFT JOIN " . DB_PREFIX . "likbet_lot_archive la USING (lot_id)";

        $where = [
            "l.`winner`=?",
        ];
        $params = [
            $data['filter_customer'],
        ];

        if (isset($data['obj_type'])) {
            $where[] = sprintf("la.obj_type IN (%s)",
                implode(',', array_map(
                        function (&$item) {
                            return '"' . $item . '"';
                        },
                        $data['obj_type']
                    )
                )
            );
        }

        if (count($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);

        }

        if (!$just_count) {
            $sql .= " GROUP BY la.lot_id";

            $sort_data = array(
                'date_played',
                'date_start',
                'date_end',
                'name',
                'current_sum',
                'obj_type',
            );

            if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
                $sql .= " ORDER BY `" . $data['sort'] . "`";
            } else {
                $sql .= " ORDER BY l.date_played";
            }

            if (isset($data['order']) && (strtolower($data['order']) == 'asc')) {
                $sql .= " ASC";
            } else {
                $sql .= " DESC";
            }

            $start = 0;
            $limit = 20;
            if (isset($data['start'])) {
                $start = ($data['start'] < 0) ? 0 : $data['start'];
            }
            if (isset($data['limit'])) {
                $limit = ($data['limit'] < 1) ? 20 : $data['limit'];
            }
            $sql .= " LIMIT " . (int)$start . ',' . (int)$limit;
        }

        $query = $this->db->query($sql, $params);

        if ($just_count) {
            $this->cache->set('wins_cnt_' . md5(serialize($data)), (int)$query->row['total'], 300, ['lots']);
            return $query->row['total'];
        } else {
            $this->cache->set('wins_' . md5(serialize($data)), $query->rows, 300, ['lots']);
            return $query->rows;
        }
    }

    /**
     * Количество выигравших и сумма выигрыша группы победителя в этапах розыгрыша
     *
     * @param array $lotteries Полученные массив из метода getLottery
     * @return array Сумма выигрышей и количество этапов розыгрыша.
     */
    public function getLotteryStagesSum($lotteries)
    {
        $data = $lotteries;
        $cached = $this->cache->get('stages_sum' . md5(serialize($data)));
        if (is_array($cached)) {
            return $cached;
        }

        $stage_percent = 0;
        $LotteryStagesSum = [];
        foreach ($lotteries as &$lottery_info) {
            $customer_id = $lottery_info['customer_info']['customer_id'];
            if ($this->customer->isLogged() && $customer_id == $this->customer->getId()) {
                $lottery_info['customer_info']['details'] = $this->url->link('account/account', '', true);
            } else {
                $lottery_info['customer_info']['details'] = $this->url->link('customer/info', 'customer_id=' . $customer_id, true);
            }

            if ($lottery_info['percent'] != $stage_percent) {
                $stage_percent = $lottery_info['percent'];
            }

            $LotteryStagesSum[$stage_percent]['count']++;
            $LotteryStagesSum[$stage_percent]['sum_prize'] += $lottery_info['prize'];

            if ($LotteryStagesSum[$stage_percent]['count'] == 1) {
                $LotteryStagesSum[$stage_percent]['customer_info'] = $lottery_info['customer_info'];
            } else {
                unset($LotteryStagesSum[$stage_percent]['customer_info']);
            }
        }

        $this->load->language('likbet/lottery');

        foreach ($LotteryStagesSum as &$stages_sum) {
            $stages_sum['sum_prize'] = $this->currency->format($stages_sum['sum_prize'], $this->session->data['currency']);
            $stages_sum['count_text'] = $stages_sum['count'] . ' ' . $this->language->get('stages_sum_note');
        }

        //сортировка по ключу (первый в массиве - это игрок получивший максимальный процент в розыгрыше)
        krsort($LotteryStagesSum);

        // индекс 0 - должен быть единственный победитель в товарах (главный победитель в деньгах)
        $LotteryStagesSum = array_values($LotteryStagesSum);

        $this->cache->set('stages_sum' . md5(serialize($data)), $LotteryStagesSum, 300, ['lots']);

        return $LotteryStagesSum;
    }

    /**
     * Установить статус получения приза
     *
     * @param int $id ID записи
     */
    public function setProcessed($id)
    {
        $sql = 'UPDATE `' . DB_PREFIX . 'likbet_lottery` SET `processed`=1 WHERE id=?';
        $this->db->query($sql, [
            $id,
        ]);
    }

    /**
     * Проверить нужно ли пользователю получать приз
     *
     * @param int $lot_id ID лота
     * @param int $winner_id ID пользователя
     * @return bool
     */
    public function shouldGetPrize($lot_id, $winner_id)
    {
        $sql = 'SELECT `processed` FROM `' . DB_PREFIX . 'likbet_lottery` WHERE lot_id=? AND winner=?';
        $query = $this->db->query($sql, [
            $lot_id,
            $winner_id,
        ]);

        if (isset($query->row['processed'])) {
            return $query->row['processed']==0;
        }

        return false;
    }
}
