<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class SpodApi extends Module
{
    public function __construct()
    {
        $this->name = 'spodapi';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Frédérik Albert';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Spod Api');
        $this->description = $this->l('Enables to load product from Spod api.');

        // Settings paths
        if (!$this->_path) {
            $this->_path = __PS_BASE_URI__ . 'modules/' . $this->name . '/';
        }
        $this->js_path = $this->_path . 'views/js/';
        $this->css_path = $this->_path . 'views/css/';
        $this->img_path = $this->_path . 'views/img/';
        $this->logo_path = $this->_path . 'logo.png';
        $this->module_path = $this->_path;
        $this->folder_file_upload = _PS_MODULE_DIR_ . $this->name . '/upload/';

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('SPODAPI_NAME')) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {

            Shop::setContext(Shop::CONTEXT_ALL);

        }

        return ( 
            parent::install() &&
            Configuration::updateValue('X-SPOD-ACCESS-TOKEN', '')
        ); 

    }

    public function uninstall()
    {
        return (
            parent::uninstall() 
            && Configuration::deleteByName('X-SPOD-ACCESS-TOKEN')
        );
    }

    public function loadAsset()
    {
        $this->addJsDefList();

        $this->context->controller->addCSS($this->_path . 'views/dist/back.css', 'all');
        $this->context->controller->addJS($this->_path . 'views/dist/back.js');
    }

    public function getContent()
    {
        $this->loadAsset();
        
        $output = '';

        $products = [];

        $page = 1;
        
        /*$taxRulesGroup = (array) TaxRulesGroup::getTaxRulesGroups();

        foreach ($taxRulesGroup as $group) {

            var_dump($group->name);
        }*/

        if (Tools::isSubmit('submit' . $this->name)) {

            $token = (string) Tools::getValue('X-SPOD-ACCESS-TOKEN');

            if ( empty($token) || !Validate::isGenericName($token) ) { 

                $output = $this->displayError($this->l('Invalid Configuration value'));

            } else {

                Configuration::updateValue('X-SPOD-ACCESS-TOKEN', $token);

                $output = $this->displayConfirmation($this->l('Settings updated'));

            }
        }

        if (Tools::isSubmit('submitgetlist')) {

            $token = (string) Configuration::get('X-SPOD-ACCESS-TOKEN');

            if ( empty($token) || !Validate::isString($token) || strlen($token) < 10) { 

                $output = $this->displayError($this->l('Invalid Configuration value'));

            } else {

                $headers = [
                    'X-SPOD-ACCESS-TOKEN' => $token
                ];

                $client = new \GuzzleHttp\Client([
                    'headers' => $headers
                ]);

                $response = $client->request('GET', 'https://rest.spod.com/articles?offset=1&limit=5');

                if ($response->getStatusCode() === 200) {

                    $body =  $response->getBody();

                    $body_decode = json_decode($body, true);

                    $products = $body_decode['items'];

                    /*foreach ($body_decode['items'] as $row) {

                        foreach ($row['images'] as $image) {

                            var_dump($image);

                        }

                        var_dump($row['id'] . ' ' . $row['title'] . ' ' . $row['description'] . ' ' . count($row['images']));

                    }  */          

                    /*$this->createCsvFile();*/

                    //$output = '<a href="#">' . $this->displayConfirmation($this->l('Download the csv file')) . '</a>';

                } else {
                    
                    $output = $this->displayError($this->l('an error occured during the file creation'));

                }

            }
        }

        return $output . $this->displayFormConfig() . $this->displayFormFetch() . $this->displayList($products, $page);
    }

    public function displayFormConfig()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Api Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('SPOD ACCESS TOKEN'),
                        'name' => 'X-SPOD-ACCESS-TOKEN',
                        'size' => 40,
                        'required' => true,
                    ],
                    [
                        'type' => 'categories',
                        'label' => $this->l('Default categories'),
                        'name' => 'categories',
                        'required' => true,
                        'tree' => array(
                            'root_category' => (int)Category::getRootCategory()->id,
                            'id' => 'id_category',
                            'name' => 'name_category',
                            'use_checkbox' => true,
                            /*'selected_categories' => array(3,4,5),
                            'disabled_categories' => array(6),*/
                            'use_search' => true,
                        ),
                        'desc' => $this->l('You can select one or more categories.')
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name, 'save' => $this->name , 'token' => Tools::getAdminTokenLite('AdminModules') ])
            ],
            'back' => [
                'desc' => $this->l('Back to List'),
                'href' => AdminController::$currentIndex . '&token=' .Tools::getAdminTokenLite('AdminModules')
            ]
        ];

        $helper->fields_value['X-SPOD-ACCESS-TOKEN'] = Tools::getValue('X-SPOD-ACCESS-TOKEN', Configuration::get('X-SPOD-ACCESS-TOKEN'));

        return $helper->generateForm([$form]);
    }

    public function displayFormFetch()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Get products list'),
                ],
                'submit' => [
                    'title' => $this->l('Get products list'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submitgetlist';

        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value['X-SPOD-ACCESS-TOKEN'] = Tools::getValue('X-SPOD-ACCESS-TOKEN', Configuration::get('X-SPOD-ACCESS-TOKEN'));

        return $helper->generateForm([$form]);
        
    }

    public function displayList(array $products, int $page) {

        $id_lang = $this->context->language->id;

        $categories = Category::getSimpleCategories($id_lang);

        $this->context->smarty->assign([
            'products' => $products,
            'page' => $page,
            'categories' => $categories
        ]);

        return $this->display(__FILE__, 'views/templates/admin/list.tpl');
    }

    /**
    * @throws PrestaShopException
    */
    protected function addJsDefList()
    {
        Media::addJsDef([
            /*'psr_icon_color' => Configuration::get('PSR_ICON_COLOR'),
            'psr_text_color' => Configuration::get('PSR_TEXT_COLOR'),
            'psr_controller_block_url' => $this->context->link->getAdminLink('AdminBlockListing'),
            'psr_controller_block' => 'AdminBlockListing',
            'psr_lang' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'block_updated' => $this->trans('Block updated', [], 'Modules.Blockreassurance.Admin'),
            'active_error' => $this->trans('Oops... looks like an error occurred', [], 'Modules.Blockreassurance.Admin'),
            'min_field_error' => $this->trans('The field %field_name% is required at least in your default language.', ['%field_name%' => sprintf('"%s"', $this->trans('Title', [], 'Admin.Global'))], 'Admin.Notifications.Error'),
            'psre_success' => $this->trans('Configuration updated successfully!', [], 'Modules.Blockreassurance.Admin'),
            'successPosition' => $this->trans('Position changed successfully!', [], 'Modules.Blockreassurance.Admin'),
            'errorPosition' => $this->trans('An error occurred when switching position', [], 'Modules.Blockreassurance.Admin'),
            'txtConfirmRemoveBlock' => $this->trans('Are you sure?', [], 'Admin.Notifications.Warning'),
            'errorRemove' => $this->trans('An error occurred when removing block', [], 'Modules.Blockreassurance.Admin'),*/
        ]);
    }
 
    private function createCsvFile(/*$content*/) {

        header('Content-Type: text/csv; charset=utf-8');

        header('Content-Disposition: attachment; filename=data.csv');

        $data = [['Product', 'Active (0/1)', 'Name', 'Categories (x,y,z...)', 'Price tax excluded', 'Tax rules ID', 'Quantity', 'Meta title', 'Meta keywords', 'Meta description'], 
                 ['', '1', '', '', '', 'Tax rules ID', 'Quantity', 'Meta title', 'Meta keywords', 'Meta description'], 
                 ['Jane Doe', 'jane@example.com', '555-555-1213']];

        $fp = fopen('php://output', 'w');

        foreach ($data as $row) {

            fputcsv($fp, $row);

            
        }

        fclose($fp);
    }



}