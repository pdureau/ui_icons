/**
 * @file
 * JavaScript behavior for UI Icons Preview in Drupal.
 */
// eslint-disable-next-line func-names
(function ($, Drupal, once) {
  /**
   * Fetches the SVG icon preview.
   *
   * @param {string} iconSetID
   *   The ID of the icon set.
   * @param {string} iconID
   *   The ID of the icon.
   * @param {Object} settings
   *   The Drupal settings object.
   * @return {Promise<string|null>}
   *   The SVG icon as a string, or null if an error occurs.
   */
  const fetchIcon = async (iconSetID, iconID, settings) => {
    const url = `${settings.path.baseUrl}ui-icons/ajax/icon?q=${iconSetID}:${iconID}`;

    try {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return await response.text();
    } catch (error) {
      throw new Error(`Error fetching icon: ${error}`);
    }
  };

  /**
   * Finds the nearest .ui-icons-preview element to a given element.
   *
   * @param {HTMLElement} element
   *   The element to start the search from.
   * @param {string} selector
   *   The selector value.
   * @param {string} stopClass
   *   The parent class to stop to avoid other field match.
   * @return {HTMLElement|null}
   *   The nearest .ui-icons-preview element, or null if not found.
   */
  const findNearest = (element, selector, stopClass) => {
    let currentElement = element;
    while (currentElement) {
      const nearest = currentElement.querySelector(selector);
      if (nearest) {
        return nearest;
      }
      currentElement = currentElement.parentElement;
      if (currentElement && currentElement.classList.contains(stopClass)) {
        break;
      }
    }
    return null;
  };

  /**
   * Handles the closing event of an autocomplete component by updating the icon preview and settings display.
   *
   * @async
   * @param {String} iconSetID
   *   The icon iconset id.
   * @param {HTMLElement} iconSelector
   *   The HTML element that contains the selected icon's value.
   *
   * @return {Promise<void>}
   *   A promise that resolves when the function completes.
   */
  async function processAutocompleteSettings(iconSetID, iconSelector) {
    const iconSettingsWrapper = findNearest(
      iconSelector,
      // @see ui_icons/templates/input--icon.html.twig
      '.ui-icons-settings-wrapper',
      'form-type--ui-icon-autocomplete',
    );

    if (!iconSettingsWrapper) {
      return;
    }

    const iconsetSettings = iconSettingsWrapper.querySelectorAll(
      '[name^=icon-settings--]',
    );

    if (!iconsetSettings) {
      return;
    }

    let found = false;
    iconsetSettings.forEach((iconsetSetting) => {
      if (
        `icon-settings--${iconSetID}` === iconsetSetting.getAttribute('name')
      ) {
        found = true;
        iconsetSetting.classList.remove('hidden');
      } else {
        iconsetSetting.classList.add('hidden');
      }
    });

    if (found === true) {
      iconSettingsWrapper.classList.remove('hidden');
    } else {
      iconSettingsWrapper.classList.add('hidden');
    }
  }

  /**
   * Handles the closing event of an autocomplete component by updating the icon preview and settings display.
   *
   * @async
   * @param {HTMLElement} iconSelector
   *   The HTML element that contains the selected icon's value.
   * @param {Object} settings
   *   Configuration settings for fetching the icon.
   *
   * @return {Promise<void>}
   *   A promise that resolves when the function completes.
   */
  async function processAutocomplete(iconSelector, settings) {
    const iconPreviewElement = findNearest(
      iconSelector,
      '.ui-icons-preview',
      'form-type--ui-icon-autocomplete',
    );
    const [iconSetID, iconID] = iconSelector.value.split(':');

    if (!iconSetID || !iconID) {
      if (iconPreviewElement) {
        iconPreviewElement.innerHTML = '';
      }
      processAutocompleteSettings(iconSetID, iconSelector);
      return;
    }

    fetchIcon(iconSetID, iconID, settings).then((icon) => {
      if (iconPreviewElement) {
        iconPreviewElement.innerHTML = icon;
      }
      processAutocompleteSettings(iconSetID, iconSelector);
    });
  }

  /**
   * UI Icons preview and settings form display.
   *
   * Bind the autocomplete form element to get the icon preview when selected
   * and match the visibility of settings if enable to show only settings for
   * the selected iconset.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.UiIconsPreview = {
    attach(context, settings) {
      const iconSelectors = once(
        'setIconPreview',
        '.ui-icons-wrapper .ui-icons-input-wrapper input',
        context,
      );

      if (!iconSelectors || iconSelectors.length === 0) {
        return;
      }

      iconSelectors.forEach((iconSelector) => {
        // Form is loaded with an existing value.
        if (iconSelector.value && iconSelector.value.indexOf(':') > -1) {
          const [iconSetID] = iconSelector.value.split(':');
          processAutocompleteSettings(iconSetID, iconSelector);
        }
        // Current Drupal core autocomplete is based on jQuery UI.
        // @see https://api.jqueryui.com/autocomplete/
        jQuery(iconSelector).autocomplete({
          change: () => processAutocomplete(iconSelector, settings),
          close: () => processAutocomplete(iconSelector, settings),
        });
      });
    },
  };
})(jQuery, Drupal, once);
