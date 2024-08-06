/* eslint-disable import/no-extraneous-dependencies, import/no-unresolved */
import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import icon from '../../../../icons/icon.svg';

/**
 * Provides the toolbar button to insert an icon element.
 *
 * @private
 */
export default class IconUi extends Plugin {
  init() {
    const editor = this.editor;
    const options = this.editor.config.get('icon');
    if (!options) {
      return;
    }

    this.dialogURL = options.dialogURL;

    const { openDialog, dialogSettings = {} } = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // This will register the icon toolbar button.
    editor.ui.componentFactory.add('icon', (locale) => {
      const command = editor.commands.get('insertIcon');
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Insert Icon'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          this.dialogURL,
          ({ settings }) => {
            editor.execute('insertIcon', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}
