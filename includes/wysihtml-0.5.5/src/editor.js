/**
 * WYSIHTML5 Editor
 *
 * @param {Element} editableElement Reference to the textarea which should be turned into a rich text interface
 * @param {Object} [config] See defaultConfig object below for explanation of each individual config option
 *
 * @events
 *    load
 *    beforeload (for internal use only)
 *    focus
 *    focus:composer
 *    focus:textarea
 *    blur
 *    blur:composer
 *    blur:textarea
 *    change
 *    change:composer
 *    change:textarea
 *    paste
 *    paste:composer
 *    paste:textarea
 *    newword:composer
 *    destroy:composer
 *    undo:composer
 *    redo:composer
 *    beforecommand:composer
 *    aftercommand:composer
 *    enable:composer
 *    disable:composer
 *    change_view
 */
(function (wysihtml5) {
    var undef;

    var defaultConfig = {
        // Give the editor a name, the name will also be set as class name on the iframe and on the iframe's body
        name: undef,
        // Whether the editor should look like the textarea (by adopting styles)
        style: true,
        // Id of the toolbar element, pass falsey value if you don't want any toolbar logic
        toolbar: undef,
        // Whether toolbar is displayed after init by script automatically.
        // Can be set to false if toolobar is set to display only on editable area focus
        showToolbarAfterInit: true,
        // With default toolbar it shows dialogs in toolbar when their related text format state becomes active (click on link in text opens link dialogue)
        showToolbarDialogsOnSelection: true,
        // Whether urls, entered by the user should automatically become clickable-links
        autoLink: true,
        // Includes table editing events and cell selection tracking
        handleTables: true,
        // Tab key inserts tab into text as default behaviour. It can be disabled to regain keyboard navigation
        handleTabKey: true,
        // Object which includes parser rules to apply when html gets cleaned
        // See parser_rules/*.js for examples
        parserRules: {tags: {br: {}, span: {}, div: {}, p: {}, b: {}, i: {}, u: {}}, classes: {}},
        // Object which includes parser when the user inserts content via copy & paste. If null parserRules will be used instead
        pasteParserRulesets: null,
        // Parser method to use when the user inserts content
        parser: wysihtml5.dom.parse,
        // By default wysihtml5 will insert a <br> for line breaks, set this to false to use <p>
        useLineBreaks: true,
        // Double enter (enter on blank line) exits block element in useLineBreaks mode.
        // It enables a way of escaping out of block elements and splitting block elements
        doubleLineBreakEscapesBlock: true,
        // Array (or single string) of stylesheet urls to be loaded in the editor's iframe
        stylesheets: [],
        // Placeholder text to use, defaults to the placeholder attribute on the textarea element
        placeholderText: undef,
        // Whether the rich text editor should be rendered on touch devices (wysihtml5 >= 0.3.0 comes with basic support for iOS 5)
        supportTouchDevices: true,
        // Whether senseless <span> elements (empty or without attributes) should be removed/replaced with their content
        cleanUp: true,
        // Whether to use div instead of secure iframe
        contentEditableMode: false,
        classNames: {
            // Class name which should be set on the contentEditable element in the created sandbox iframe, can be styled via the 'stylesheets' option
            composer: "wysihtml5-editor",
            // Class name to add to the body when the wysihtml5 editor is supported
            body: "wysihtml5-supported",
            // classname added to editable area element (iframe/div) on creation
            sandbox: "wysihtml5-sandbox",
            // class on editable area with placeholder
            placeholder: "wysihtml5-placeholder",
            // Classname of container that editor should not touch and pass through
            uneditableContainer: "wysihtml5-uneditable-container"
        },
        // Browsers that support copied source handling will get a marking of the origin of the copied source (for determinig code cleanup rules on paste)
        // Also copied source is based directly on selection - 
        // (very useful for webkit based browsers where copy will otherwise contain a lot of code and styles based on whatever and not actually in selection).
        // If falsy value is passed source override is also disabled
        copyedFromMarking: '<meta name="copied-from" content="wysihtml5">'
    };

    wysihtml5.Editor = wysihtml5.lang.Dispatcher.extend(
            /** @scope wysihtml5.Editor.prototype */ {
                constructor: function (editableElement, config) {
                    this.editableElement = typeof (editableElement) === "string" ? document.getElementById(editableElement) : editableElement;
                    this.config = wysihtml5.lang.object({}).merge(defaultConfig).merge(config).get();
                    this._isCompatible = wysihtml5.browser.supported();

                    // merge classNames
                    if (config && config.classNames) {
                        wysihtml5.lang.object(this.config.classNames).merge(config.classNames);
                    }

                    if (this.editableElement.nodeName.toLowerCase() != "textarea") {
                        this.config.contentEditableMode = true;
                        this.config.noTextarea = true;
                    }
                    if (!this.config.noTextarea) {
                        this.textarea = new wysihtml5.views.Textarea(this, this.editableElement, this.config);
                        this.currentView = this.textarea;
                    }

                    // Sort out unsupported/unwanted browsers here
                    if (!this._isCompatible || (!this.config.supportTouchDevices && wysihtml5.browser.isTouchDevice())) {
                        var that = this;
                        setTimeout(function () {
                            that.fire("beforeload").fire("load");
                        }, 0);
                        return;
                    }

                    // Add class name to body, to indicate that the editor is supported
                    wysihtml5.dom.addClass(document.body, this.config.classNames.body);

                    this.composer = new wysihtml5.views.Composer(this, this.editableElement, this.config);
                    this.currentView = this.composer;

                    if (typeof (this.config.parser) === "function") {
                        this._initParser();
                    }

                    this.on("beforeload", this.handleBeforeLoad);
                },

                handleBeforeLoad: function () {
                    if (!this.config.noTextarea) {
                        this.synchronizer = new wysihtml5.views.Synchronizer(this, this.textarea, this.composer);
                    } else {
                        this.sourceView = new wysihtml5.views.SourceView(this, this.composer);
                    }
                    if (this.config.toolbar) {
                        this.toolbar = new wysihtml5.toolbar.Toolbar(this, this.config.toolbar, this.config.showToolbarAfterInit);
                    }
                },

                isCompatible: function () {
                    return this._isCompatible;
                },

                clear: function () {
                    this.currentView.clear();
                    return this;
                },

                getValue: function (parse, clearInternals) {
                    return this.currentView.getValue(parse, clearInternals);
                },

                setValue: function (html, parse) {
                    this.fire("unset_placeholder");

                    if (!html) {
                        return this.clear();
                    }

                    this.currentView.setValue(html, parse);
                    return this;
                },

                cleanUp: function (rules) {
                    this.currentView.cleanUp(rules);
                },

                focus: function (setToEnd) {
                    this.currentView.focus(setToEnd);
                    return this;
                },

                /**
                 * Deactivate editor (make it readonly)
                 */
                disable: function () {
                    this.currentView.disable();
                    return this;
                },

                /**
                 * Activate editor
                 */
                enable: function () {
                    this.currentView.enable();
                    return this;
                },

                isEmpty: function () {
                    return this.currentView.isEmpty();
                },

                hasPlaceholderSet: function () {
                    return this.currentView.hasPlaceholderSet();
                },

                destroy: function () {
                    if (this.composer && this.composer.sandbox) {
                        this.composer.sandbox.destroy();
                    }
                    if (this.toolbar) {
                        this.toolbar.destroy();
                    }
                    this.off();
                },

                parse: function (htmlOrElement, clearInternals, customRules) {
                    var parseContext = (this.config.contentEditableMode) ? document : ((this.composer) ? this.composer.sandbox.getDocument() : null);
                    var returnValue = this.config.parser(htmlOrElement, {
                        "rules": customRules || this.config.parserRules,
                        "cleanUp": this.config.cleanUp,
                        "context": parseContext,
                        "uneditableClass": this.config.classNames.uneditableContainer,
                        "clearInternals": clearInternals
                    });
                    if (typeof (htmlOrElement) === "object") {
                        wysihtml5.quirks.redraw(htmlOrElement);
                    }
                    return returnValue;
                },

                /**
                 * Prepare html parser logic
                 *  - Observes for paste and drop
                 */
                _initParser: function () {
                    var oldHtml;

                    if (wysihtml5.browser.supportsModernPaste()) {
                        this.on("paste:composer", function (event) {
                            event.preventDefault();
                            oldHtml = wysihtml5.dom.getPastedHtml(event);
                            if (oldHtml) {
                                this._cleanAndPaste(oldHtml);
                            }
                        }.bind(this));

                    } else {
                        this.on("beforepaste:composer", function (event) {
                            event.preventDefault();
                            var scrollPos = this.composer.getScrollPos();

                            wysihtml5.dom.getPastedHtmlWithDiv(this.composer, function (pastedHTML) {
                                if (pastedHTML) {
                                    this._cleanAndPaste(pastedHTML);
                                }
                                this.composer.setScrollPos(scrollPos);
                            }.bind(this));

                        }.bind(this));
                    }
                },

                _cleanAndPaste: function (oldHtml) {
                    var cleanHtml = wysihtml5.quirks.cleanPastedHTML(oldHtml, {
                        "referenceNode": this.composer.element,
                        "rules": this.config.pasteParserRulesets || [{"set": this.config.parserRules}],
                        "uneditableClass": this.config.classNames.uneditableContainer
                    });
                    this.composer.selection.deleteContents();
                    this.composer.selection.insertHTML(cleanHtml);
                }
            });
})(wysihtml5);
