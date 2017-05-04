<?php

class ModelLikbetConfirmation extends Model
{
    public function add($customer_id, $hash)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "likbet_confirmation` SET `customer_id`=?, `hash`=?, `date_added`=NOW()",
            [
                $customer_id,
                $hash,
            ]
        );
    }

    public function get($customer_id, $hash)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "likbet_confirmation` WHERE `customer_id`=? AND `hash`=? AND `date_added` > NOW() - INTERVAL 1 DAY ORDER BY `date_added` DESC LIMIT 1",
            [
                $customer_id,
                $hash,
            ]
        );

        return $query->row;
    }

    public function delete($confirm_id)
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "likbet_confirmation` WHERE `confirm_id`=?",
            [
                $confirm_id,
            ]
        );
    }

    public function deactivateCustomer($customer_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "customer SET `status`=0 WHERE customer_id = ?", [
            (int)$customer_id,
        ]);
    }

}