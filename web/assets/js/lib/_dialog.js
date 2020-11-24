var _dialog = {
    create: function($title, $content, $actions) {
        return $('<dialog class="mdl-dialog"></dialog>')
            .append($title)
            .append($content)
            .append($actions)
    },
    title: function(title) {
        return $('<div class="mdl-dialog__title">' + title + '</div>');
    },
    content: function(content) {
        return $('<div class="btw-dialog__content mdl-dialog__content npb"></div>')
            .append(content)
    },
    actions: function(buttons) {
        const $container = $('<div class="mdl-dialog__actions mdl-dialog__actions--full-width"></div>');
        const $grid = $('<div class="mdl-grid"></div>');
        const $cell = $('<div class="mdl-cell mdl-cell--12-col"></div>');

        $container.append($grid);
        $grid.append($cell);

        for (const button of buttons) {
            const $button = $('<button/>')
                .addClass(`mdl-button ${button.cssClass}`)
                .text(button.label);

            if (button.onClick) {
                $button.click(button.onClick)
            }

            $cell.append($button)
        }

        setTimeout(function() {
            $cell.find('.mdl-button').last().focus()
        }, 100)

        return $container;
    }
}