<?php

class ControllerExtensionModuleListWinners extends Controller
{
    public function index($setting)
    {
        $this->load->model('likbet/lottery');
        $this->load->language('likbet/lottery');

        $result_lottery = $this->model_likbet_lottery->getLottery($setting);
        $link = $this->url->link('likbet/winner', '', true);

        $item_tpl = html_entity_decode($setting['tpl']);

        $loader = new Twig_Loader_Array([
            'index' => $item_tpl,
        ]);
        $twig = new Twig_Environment($loader);


        $result = $twig->render('index', [
            'lottery' => $result_lottery,
            'link' => $link,
            'i18l' => $this->language->getLabels()
        ]);

        return $result;
    }
}
