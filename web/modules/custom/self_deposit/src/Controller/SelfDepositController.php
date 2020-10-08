<?php

// namespace Drupal\self_deposit\Controller;

// use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Url;
// use Drupal\node\NodeTypeInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\HttpFoundation\RedirectResponse;

// /**
//  * Class SelfDepositController.
//  */
// class SelfDepositController extends ControllerBase {

//   /**
//    * Drupal\Core\Entity\EntityFormBuilderInterface definition.
//    *
//    * @var \Drupal\Core\Entity\EntityFormBuilderInterface
//    */
//   protected $entityFormBuilder;
//   /**
//    * Drupal\Core\Session\AccountProxy definition.
//    *
//    * @var \Drupal\Core\Session\AccountProxy
//    */
//   protected $currentUser;

//   /**
//    * {@inheritdoc}
//    */
//   public static function create(ContainerInterface $container) {
//     $instance = parent::create($container);
//     $instance->entityFormBuilder = $container->get('entity.form_builder');
//     $instance->currentUser = $container->get('current_user');
//     $instance->moduleHandler = $container->get('module_handler');
//     $instance->pathCurrent = $container->get('path.current');
//     $instance->pathValidator = $container->get('path.validator');
//     $instance->requestStack = $container->get('request_stack');
//     return $instance;
//   }

//   /**
//    * Change the form mode based on roles.
//    *
//    * @return string
//    *   Return form in proper view mode.
//    */
//   public function content(NodeTypeInterface $node_type) {
//     $node = $this->entityTypeManager()->getStorage('node')->create(
//       ['type' => $node_type->id()]
//     );

//     if ($this->currentUser->isAuthenticated()) {
//       $roles = $this->currentUser->getRoles();
//       if (in_array('depositor', $roles)) {
//         return $this->entityFormBuilder()->getForm($node, 'default');
//       }
//       else {
//         return $this->entityFormBuilder()->getForm($node, 'simple_ingest');
//       }
//     }
//     else {
//       $current_path = $this->pathCurrent->getPath();
//       $url_object = $this->pathValidator->getUrlIfValid($current_path);
//       $route_name = $url_object->getRouteName();
//       $route_parameters = $url_object->getRouteParameters();
//       $current_url = Url::fromRoute($route_name, $route_parameters);

//       if ($this->moduleHandler->moduleExists('cas')) {
//         $url = Url::fromRoute('cas.login', ['destination' => $current_url->toString()])->toString();
//       }
//       else {
//         $url = Url::fromRoute('user.login', ['destination' => $current_url->toString()])->toString();
//       }
//       $current_request = $this->requestStack->getCurrentRequest();
//       $current_request->query->set('destination', $url);
//       return new RedirectResponse($url);
//     }
//   }

// }
