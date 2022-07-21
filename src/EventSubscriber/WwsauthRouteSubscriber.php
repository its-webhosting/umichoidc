<?php

namespace Drupal\wwsauth\EventSubscriber;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
class WwsauthRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $config =  \Drupal::config('openid_connect.settings');
    $login = $config->get('user_login_display');

    foreach ($collection->all() as $route) {
      // Hide taxonomy pages from unprivileged users.
      if (strpos($route->getPath(), '/user/login') === 0) {
        var_dump('hi');
        #$route->setPath('/');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
