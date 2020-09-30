console.log('running wp-media script');

jQuery(document).ready(function($){

  var mediaUploader;

  $('#upload_file').click(function(e) {
    clickedButton = jQuery(this);
    e.preventDefault();
    // If the uploader object has already been created, reopen the dialog
      if (mediaUploader) {
      mediaUploader.open();
      return;
    }
    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: 'Choose or Upload CSV File',
      button: {
      text: 'Choose or Upload CSV File'
    }, multiple: false });

    // When a file is selected, grab the URL and set it as the text field's value
    mediaUploader.on('select', function() {
      attachment = mediaUploader.state().get('selection').first().toJSON();

      var place = $('#upload_path');
      place.val(attachment.url);
      delete clickedButton;
    });
    // Open the uploader dialog
    mediaUploader.open();

  });

});
