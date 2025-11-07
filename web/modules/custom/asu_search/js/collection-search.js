(function (Drupal, once) {
Drupal.behaviors.asu_search = {
  attach(context) {
    once('asu_search', 'span[data-bs-toggle="popover"]', context).forEach(function (el) {
        return new bootstrap.Popover(el)
    });
  }
};
}(Drupal, once));