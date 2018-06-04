<?php
namespace Zero\library;

class Route
{

    /**
     * @var string
     */
    public $url = NULL;

    /**
     * @var string
     */
    public $module = NULL;

    /**
     * @var string
     */
    public $controller = NULL;

    /**
     * @var string
     */
    public $action = NULL;


    public function __construct()
    {

        if(!get_magic_quotes_gpc()) {
            $_POST = new_addslashes($_POST);
            $_GET = new_addslashes($_GET);
            $_REQUEST = new_addslashes($_REQUEST);
            $_COOKIE = new_addslashes($_COOKIE);
        }
        $this->init();
    }

    private function init()
    {
        $this->url = isset($_GET['r']) ? $_GET['r'] : NULL;
        $url = $this->url;
        if(  $url !== NULL || $_SERVER['REQUEST_URI']=='/'){
            //gets default config of route
            $route_conf = Config::get('route')['default'];
            $url = trim($url, '/'); //去掉左右两边的/
            $url_array = explode('/', $url);

            //gets module
            $this->module = !empty($url_array[0]) ? ucwords(array_shift($url_array)) : $route_conf['module'];

            //gets controller
            $this->controller = !empty($url_array[0])  ? ucwords(array_shift($url_array)) : $route_conf['controller'];

            //gets action
            $this->action = !empty($url_array[0])  ? strtolower(array_shift($url_array)) : $route_conf['action'];

            $this->controller_low = strtolower($this->controller);

            //gets params after action
            if( !empty($url_array) ){
                for($i=0; $i<count($url_array); $i+=2){
                    if(isset($url_array[$i+1])){
                        $_GET[$url_array[$i]] = $url_array[$i+1];
                    }
                }
            }

            $class = '\App\\'.$this->module.'\\Controller\\'.$this->controller;
            //Add decorator
            $decorators = [];
            $decorators_conf = Config::get('decorators');
            $decorators = $decorators_conf['output_decorators'];

            //gets global object of  decorators 
            if( !empty($decorators) ){
                foreach ($decorators as $key => $value) {
                    $dec_obj[] = new $value($this->module, $this->controller, $this->action);
                }  
            }
            
            foreach ($dec_obj as $key => $value) {
                $value->before_request();
            }
            $object = new $class($this->module, $this->controller, $this->action);
            new Factory($this->module, $this->controller, $this->action);

            $method = $this->action;
            $result = $object->$method($this->module, $this->controller, $this->action);
            foreach ($dec_obj as $key => $value) {
                $value->after_request($result);
            }
        }
    }
}