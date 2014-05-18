<?php namespace Brain;

class Container extends \Pimple {

    private static $brain;

    private $brain_modules;

    public static function boot( \Pimple $container, $with_modules = TRUE, $with_hooks = TRUE ) {
        if ( is_null( self::$brain ) ) {
            self::$brain = $container;
            self::$brain['embedded'] = function () {
                return new static;
            };
            $instance = self::$brain['embedded'];
            $instance->brain_modules = new \SplObjectStorage;
            if ( $with_modules !== FALSE ) static::bootModules( $instance, $with_hooks );
        }
        return self::$brain['embedded'];
    }

    public static function flush() {
        self::$brain = NULL;
    }

    public static function instance() {
        if ( is_null( self::$brain ) || ! isset( self::$brain['embedded'] ) ) {
            throw new \DomainException;
        }
        return self::$brain['embedded'];
    }

    public function addModule( Module $module ) {
        if ( ! $this->getModules() instanceof \SplObjectStorage ) {
            throw new \DomainException;
        }
        $this->getModules()->attach( $module );
    }

    public function get( $id = '' ) {
        if ( ! is_string( $id ) ) throw new \InvalidArgumentException;
        return $this[$id];
    }

    public function set( $id = '', $value = NULL ) {
        if ( ! is_string( $id ) ) throw new \InvalidArgumentException;
        $this[$id] = $value;
    }

    protected function getModules() {
        return $this->brain_modules;
    }

    protected static function bootModules( Container $instance, $with_hooks = TRUE ) {
        // use this hook to register modules via Brain\Container::addModule()
        // or to add params/services to container thanks to the instance passed as action argument
        if ( $with_hooks ) do_action( 'brain_init', $instance );
        $modules = $instance->getModules();
        if ( ! $modules instanceof \SplObjectStorage ) {
            throw new \DomainException;
        }
        $modules->rewind();
        while ( $modules->valid() ) {
            $module = $modules->current();
            $module->getBindings( $instance );
            $module->boot( $instance );
            $modules->next();
        }
        if ( $with_hooks ) do_action( 'brain_loaded', $instance );
    }

}