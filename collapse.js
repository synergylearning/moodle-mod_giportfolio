/*global M*/
M.mod_giportfolio_collapse = {
    init: function(Y) {
        "use strict";
        var toc = new Y.YUI2.widget.TreeView('giportfolio-toc');
        toc.render();
    }
};