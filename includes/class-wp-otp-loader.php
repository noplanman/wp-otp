<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    Wp_Otp
 * @subpackage Loader
 * @since      0.1.0
 */

namespace Wp_Otp;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout the plugin and register them with the WordPress API.
 *
 * @since 0.1.0
 */
class Wp_Otp_Loader {
	/**
	 * The actions registered with WordPress to fire when the plugin loads.
	 *
	 * @since  0.1.0
	 * @access private
	 * @var    array $actions
	 */
	private array $actions;

	/**
	 * The filters registered with WordPress to fire when the plugin loads.
	 *
	 * @since  0.1.0
	 * @access private
	 * @var    array $filters
	 */
	private array $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->actions = [];
		$this->filters = [];
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook          The name of the WordPress action that is being registered.
	 * @param object $component     A reference to the instance of the object on which the action is defined.
	 * @param string $callback      The name of the function definition on the $component. Default is the $hook name.
	 * @param int    $priority      The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( string $hook, object $component, string $callback = '', $priority = 10, $accepted_args = 1 ): void {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback ?: $hook, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook          The name of the WordPress filter that is being registered.
	 * @param object $component     A reference to the instance of the object on which the filter is defined.
	 * @param string $callback      The name of the function definition on the $component. Default is the $hook name.
	 * @param int    $priority      The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_filter( string $hook, object $component, string $callback = '', $priority = 10, $accepted_args = 1 ): void {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback ?: $hook, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since  0.1.0
	 * @access private
	 *
	 * @param array  $hooks         The collection of hooks that is being registered (that is, actions or filters).
	 * @param string $hook          The name of the WordPress filter that is being registered.
	 * @param object $component     A reference to the instance of the object on which the filter is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      The priority at which the function should be fired.
	 * @param int    $accepted_args The number of arguments that should be passed to the $callback.
	 *
	 * @return array The collection of actions and filters registered with WordPress.
	 */
	private function add( array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args ): array {
		$hooks[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since 0.1.0
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				[ $hook['component'], $hook['callback'] ],
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				[ $hook['component'], $hook['callback'] ],
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
