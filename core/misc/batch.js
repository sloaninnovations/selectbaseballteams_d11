/**
 * @file
 * Drupal's batch API.
 */

(function ($, Drupal) {
  function getUrlWithOp(url, newOp) {
    // Replace the operation parameter instead of just appending it to
    // avoid problems with CDNs that modify query string ordering.
    if (url.indexOf('op=do_nojs') !== -1) {
      return url.replace('op=do_nojs', `op=${newOp}`);
    }
    return `${url}&op=${newOp}`;
  }

  /**
   * Attaches the batch behavior to progress bars.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.batch = {
    attach(context, settings) {
      const batch = settings.batch;
      const $progress = $(once('batch', '[data-drupal-progress]'));
      let progressBar;

      // Success: redirect to the summary.
      function updateCallback(progress, status, pb) {
        if (progress === '100') {
          pb.stopMonitoring();
          window.location = getUrlWithOp(batch.uri, 'finished');
        }
      }

      function errorCallback(pb) {
        $progress.prepend($('<p class="error"></p>').html(batch.errorMessage));
        $('#wait').hide();
      }

      if ($progress.length) {
        progressBar = new Drupal.ProgressBar(
          'updateprogress',
          updateCallback,
          'POST',
          errorCallback,
        );
        progressBar.setProgress(-1, batch.initMessage);
        progressBar.startMonitoring(getUrlWithOp(batch.uri, 'do'), 10);
        // Remove HTML from no-js progress bar.
        $progress.empty();
        // Append the JS progressbar element.
        $progress.append(progressBar.element);
      }
    },
  };
})(jQuery, Drupal);
