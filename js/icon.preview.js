/**
 * @file
 * JavaScript behavior for UI Icons preview in Drupal.
 */
((Drupal, drupalSettings, once) => {
  /**
   * @namespace
   */
  Drupal.Icon = {};

  Drupal.Icon.loadIconPreview = async function (data) {
    try {
      const iconData = await fetch(Drupal.url('ui-icons/ajax/preview/icons'), {
        method: 'POST',
        body: JSON.stringify(data),
      });
      if (!iconData.ok) throw new Error('Failed to get data!');
      const iconsPreview = await iconData.json();
      for (const [icon_full_id, icon_preview] of Object.entries(iconsPreview)) {
        // Standard library mode, direct replacement.
        if (!data.target_input_label) {
          const icon_target = document.querySelector(
            `.icon-preview-load[data-icon-id='${icon_full_id}']`,
          );
          icon_target.outerHTML = icon_preview;
          continue;
        }

        // Form with input mode, for icon picker.
        const icon_target = document.querySelector(
          `.icon-preview-load[value='${icon_full_id}']`,
        );
        const icon_label = document.querySelector(
          `label[for='${icon_target.id}']`,
        );
        icon_label.innerHTML = icon_preview;
      }
    } catch (err) {
      alert(`Something went wrong! ${err.message}`);
    }
  };

  /**
   * UI Icons preview loader.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.IconPreview = {
    attach(context, settings) {
      once('loadIconPreview', 'body', context).forEach(() => {
        if (!settings.ui_icons_preview_data) {
          return;
        }
        Drupal.Icon.loadIconPreview(settings.ui_icons_preview_data);
      });
    },
  };
})(Drupal, drupalSettings, once);
