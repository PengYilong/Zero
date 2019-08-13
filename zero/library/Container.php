<?php
namespace zero;

use ArrayAccess;
use ReflectionClass;
use ReflectionException;
use InvalidArgumentException;
use Countable;
use zero\exceptions\ClassNotFoundException;

class Container implements ArrayAccess, Countable{

    /**
     * @var container
     */
    protected static $instance;

    /**
     * the classes instantiated
     */
    public $instances = [];

    protected $bind = [
        'application' => Application::class,
        'config' => Config::class,
        'env' => Env::class,
        'request' => Request::class,
        'session' => Session::class,
        'route' => Route::class,
        'middleware' => Middleware::class,
        'hook' => Hook::class,
    ];

    private function __construct()
    {
    }

    /**
     * static method for the function make  
     */
    public static function get($class, $args = [], $newInstance = false)
    {
       return self::getInstance()->make($class, $args, $newInstance);
    }

    /**
     * @return new instance 
     */
    public function make(string $class, $args = [], $newInstance = false)
    {
        if( true == $args ){
            $newInstance = true;
            $args = [];
        }
        $realClass = $this->bind[$class] ?? $class;
        
        if( isset($this->instances[$realClass]) && !$newInstance ){
            return $this->instances[$realClass]; 
        }

        try {
            $ref = new ReflectionClass($realClass);
            $constructor = $ref->getConstructor();
            if( $constructor ){
                $params = $constructor->getParameters();
                if( !empty( $params ) ){
                    foreach($params as $key=>$value ){
                        if( isset($args[$key]) ){
                            $realArgs[] = $args[$key];
                        } else if( $value->getClass() ){
                            $realArgs[] = $this->make($value->getClass()->getName()); 
                        } else if( $value->isDefaultValueAvailable() ){
                            $realArgs[] = $value->getDefaultValue();
                        } else {
                            throw new InvalidArgumentException('The param of the method is missed:'. $value->getName());
                        } 
                    }
                } else {
                    $realArgs = $params;
                }
                $object = $ref->newInstanceArgs($realArgs);
            } else {
                $object = $ref->newInstance();
            }

            if(!$newInstance){
                $this->instances[$realClass] = $object;
            }

            return $object;
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('Class Not Found:'. $e->getMessage());
        }
    }

    /**
     * get current instance
     */
    public static function getInstance()
    {
        if( null === static::$instance ){
            self::$instance = new static;
        }   
        return self::$instance;
    }

    public function offsetExists ( $offset ) : bool 
    {
        return isset($this->instances[$offset]);
    }

    public function offsetGet( $offset )
    {
        return $this->__get( $offset );
    } 

    public function offsetSet( $offset, $value) : void
    {
        if( is_null($offset) ){
            $this->instances[] = $value;
        } else {
            $this->instances[$offset] = $value;
        }
    } 

    public function offsetUnset( $offset ) : void
    {
        unset($this->instances[$offset]); 
    } 

    public function count()
    {
        return count($this->instances);
    }

    public function __get($class)
    {  
        return self::get($class);
    }
}