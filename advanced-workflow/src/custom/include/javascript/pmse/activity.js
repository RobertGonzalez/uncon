/**
 * Gets custom action context menu addition
 * @return {object} Action definition for a context menu
 */
AdamActivity.prototype.customContextMenuActions = function() {
    // You can add as many objects to this array as needed
    return [{
        // Maps to the back end element
        name: 'DATA_ENRICHMENT',
        text: 'Enrich Person/Company Data'
    }]
}

/**
 * Gets a custom action definition for rendering
 * @param {string} type The action type
 * @param {object} w Window definition
 * @return {object} Definition for the custom action
 */
AdamActivity.prototype.customGetAction = function(type, w) {
    switch (type) {
        case 'DATA_ENRICHMENT':
        // We should really use translate here for i18n
        var actionText = 'Enrich record data using Hint';
        var actionCSS = 'adam-menu-icon-configure';
        // This disables the settings menu
        var disabled = true;
        // This returns the settings for this element
        return {
            actionText: actionText,
            actionCSS: actionCSS,
            disabled: disabled
        };
    }
}

/**
 * Needed to define the modal that pops up with a form
 * @param {string} type The action type
 * @return {object} Definition needed for a modal
 */
AdamActivity.prototype.customGetWindowDef = function(type) {
    switch(type) {
        case 'DATA_ENRICHMENT':
            var wWidth = 500;
            var wHeight = 140;
            var wTitle = 'Enrich record data using Hint';
            return {wWidth: wWidth, wHeight: wHeight, wTitle: wTitle};
    }
}
