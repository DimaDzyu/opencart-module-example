<?php

/**
 * Class ModelLikbetDateformat
 *
 * Класс представления даты на сайте
 *
 * @author Dima Dzyubenko
 * @version 1.0
 */

class ModelLikbetDate extends Model
{

    /**
     * Вернуть отформатированную дату
     *
     * @param string $date
     * @param string $format
     * @return string Отформатированная дата
     */
    public function DateFormat($date, $format = '%e %b %Y')
    {
        setlocale(LC_TIME, getLocaleString($this->config));

        $this->load->language('likbet/date');
        $lang_site = $this->config->get('config_language');

        $now = time();
        $unix_date = strtotime($date);
        $difference = intval($now-$unix_date);

        if($difference < 86400 && $difference >= 0){

            if($difference < 60)
            {
                $res_format =  $this->language->get('just_now');

            } else if($difference < 3600) {

                $minute = intval($difference/60);

                if($lang_site == 'ru-ru'){

                    $arMinuteVariant_1 = array(1, 21, 31, 41, 51);
                    $arMinuteVariant_2 = array(2, 3, 4, 22, 23, 24, 32, 33, 34, 42, 43, 44, 52, 53, 54);

                    if(in_array($minute, $arMinuteVariant_1)){
                        $res_format =  $minute.' '.$this->language->get('minute_1');
                    } elseif(in_array($minute, $arMinuteVariant_2)) {
                        $res_format = $minute.' '.$this->language->get('minute_2');
                    } else {
                        $res_format = $minute.' '.$this->language->get('minute_3');
                    }
                } else {
                    if($minute == 1){
                        $res_format = $minute.' '.$this->language->get('minute_1');
                    } else {
                        $res_format = $minute.' '.$this->language->get('minute_2');
                    }
                }
            } else {
                $hour = intval($difference/3600);

                if($lang_site == 'ru-ru'){

                    $arHourVariant_1 = array(1, 21);
                    $arHourVariant_2 = array(2, 3, 4, 22, 23, 24);

                    if(in_array($hour, $arHourVariant_1)){
                        $res_format =  $hour.' '.$this->language->get('hour_1');
                    } elseif(in_array($hour, $arHourVariant_2)) {
                        $res_format =  $hour.' '.$this->language->get('hour_2');
                    } else {
                        $res_format =  $hour.' '.$this->language->get('hour_3');
                    }
                } else {
                    if($hour == 1){
                        $res_format =  $hour.' '.$this->language->get('hour_1');
                    } else {
                        $res_format =  $hour.' '.$this->language->get('hour_2');
                    }
                }
            }

        } else {
            $res_format = strftime($format, strtotime($date));
        }

        return $res_format;
    }
}