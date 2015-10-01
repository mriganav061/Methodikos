/* DOKUWIKI:include lib/Supa.js */
// vim :set ts=4 sw=4 expandtab
/**
 * Supa helper plugin
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Christoph Linder
 */
var supa_handler = {
    preview_scaler: "",
    preview_width: "100px",
    preview_height: "100px",
    sectok: "",
    namespace: "",
    default_filename: "",

    applet_id: "Supa__Applet",
    applet_name: "Supa__Applet",
    file_extension: "png",

    msg: function (text, style) {
        var msgbox = $('media__left'),
            div = document.createElement('div'),
            tn = document.createTextNode(text),
            c = "",
      before = null;

        if (!msgbox) {
            alert(text);
        }

        div.appendChild(tn);
        switch (style) {
        case 0:
            c = "success";
            break;
        case 1:
            c = 'info';
            break;
        default:
            c = "error";
        }
        div.setAttribute("class", c);

        if (msgbox.childNodes) {
            before = msgbox.childNodes[0];
        }
        msgbox.insertBefore(div, before);

    },

    pasteButtonHandler: function (e) {
        cleanMsgArea();
        var s = new Supa(),
            supaApplet = document.getElementsByName(supa_handler.applet_name)[0],
      err;

        if (!s.ping(supaApplet)) {
            alert(LANG['plugins']['supa']["err_not_loaded_yet"]);
            return;
        }
        try {
            err = supaApplet.pasteFromClipboard();
            switch (err) {
            case 0:
                /* no error */
                break;
            case 1:
                alert(LANG['plugins']['supa']["err_clipboard_unknown_error"]);
                break;
            case 2:
                alert(LANG['plugins']['supa']["err_clipboard_empty"]);
                break;
            case 3:
                alert(LANG['plugins']['supa']["err_clipboard_content_unsupported"]);
                break;
            default:
                alert(LANG['plugins']['supa']["err_clipboard_error_code_unknown"] + err);
            }
        } catch (ex) {
            if (typeof ex === "object") {
                alert("Internal exception: " + ex.toString());
            } else {
                alert("Internal exception: " + ex);
            }
        }
    },

    uploadButtonHandler: function () {
        cleanMsgArea();

        var e_filename = $("supa__filename"),
            filename = e_filename.value,
      s,
      supaApplet,
      encodedData,
      response,
      loc;

        if (!filename) {
            alert(LANG['plugins']['supa']['err_need_filename']);
            e_filename.focus();
            return;
        }
    //FIXME: check the backslashing!
        if (!e_filename.value.toLowerCase().match("\." + supa_handler.file_extension + "$")) {
            e_filename.value += "." + supa_handler.file_extension;
        }

        //var encodedData = "Hello World";
        s = new Supa();
        supaApplet = document.getElementsByName(supa_handler.applet_name)[0];
        if (!s.ping(supaApplet)) {
            alert(LANG['plugins']['supa']["err_not_loaded_yet"]);
            return;
        }
        encodedData = supaApplet.getEncodedString();
        if (!encodedData) {
            alert(LANG['plugins']['supa']['err_paste_image_first']);
            return;
        }

        //FIXME: implement overwriting
        try {
            response = s.ajax_post(
        DOKU_BASE + 'lib/exe/mediamanager.php',
        encodedData,
        'Filedata',
        filename + ".supascreenshot",
        [
          {
            name: 'sectok',
            value: $("supa__sectok").value
          },
          {
            name: 'ns',
            value: $("supa__ns").value
          },
          {
            name: 'id',
            value: $("supa__filename").value + ".supascreenshot"
          },
          {
            name: 'ow',
            value: '1'
          }
        ]
      );
            //alert( "response: "+response );
            if (response === "ok") {
                // list() requires a location...
                // as I don't know how to instantiate one, we're simulating...
                loc = {
                    search: "?ns=" + $("supa__ns").value
                };

                media_manager.list(null, loc);
                //FIXME: both mediamanager.list and buildElements should paint the button!
                // as we're overriding media_manager.list() this should already work but it dowsn't :(
                // but neither does a direct call to buildElements :(
                //supa_handler.buildElements();
            } else {
                supa_handler.msg(response);
            }

        } catch (ex) {
            supa_handler.msg("Exception: " + ex);
        }
    },

    createSupaApplet: function () {
        var where, root, html;

        function t(txt) {
            return LANG['plugins']['supa'][txt];
        }

        function insertAfter(ref, newNode) {
            ref.parentNode.insertBefore(newNode, ref.nextSibling);
        }

        where = $("dw__flashupload");
        if (!where) {
            return false;
        }

        root = document.createElement("div");
        root.id = "supa__upload";
        root.style.display = "none";

        html = "";
        html += "<div class='upload'>";
        html += "  <button id='supa__pastebutton' type='button'>" + t("label_paste_image") + "</button><br/>";
        html += "  <div style='border: 1px solid black;'>";
        html += "    <applet id='" + supa_handler.applet_id + "' name='" + supa_handler.applet_name + "'";
        html += "      archive='" + DOKU_BASE + "lib/plugins/supa/lib/Supa.jar'";
        html += "      code='de.christophlinder.supa.SupaApplet.class'";
        html += "      width='" + supa_handler.preview_width + "'";
        html += "      height='" + supa_handler.preview_height + "'";
        html += "      >";
        html += "      <param name='imagecodec' value='png'/>";
        html += "      <param name='previewscaler' value='" + supa_handler.preview_scaler + "'/>";
        html += "      <param name='encoding' value='base64'/>";
        html += "      " + t("txt_no_java");
        html += "    </applet>";
        html += "  </div>";
        html += "  <br/>";

        html += "  <input id='supa__sectok' type='hidden' value='" + supa_handler.sectok + "'/>";
        html += "  <label for='supa__ns'>" + t("prompt_namespace") + "</label>";
        html += "  <input id='supa__ns' value='" + supa_handler.namespace + "'/>";
        html += "  <span title='" + t("txt_required_hint") + "'>" + t("txt_required") + "</span>";
        html += "  <br/>";

        html += "  <label for='supa__filename'>" + t("prompt_filename") + "</label>";
        html += "  <input id='supa__filename' value='" + supa_handler.default_filename + "'/>";
        html += "  <span title='" + t("txt_required_hint") + "'>" + t("txt_required") + "</span>";
        html += "  <br/>";

        html += "  <button id='supa__uploadbutton' type='button'>" + t("txt_upload_image") + "</button>";
        html += "  <br/>";
        html += "</div>";

        root.innerHTML = html;

        insertAfter(where, root);

        return true;

    },

    createSupaButton: function () {
        var uploadForm = $('dw__upload'),
        supaDiv = $('supa__upload'),
      spacer,
      icon,
      pastebutton,
      uploadbutton;

        if (!supaDiv || !uploadForm) {
            return false;
        }

        spacer = document.createElement('span');
        spacer.innerHTML = '&nbsp;&nbsp;&nbsp;';
        uploadForm.appendChild(spacer);

        icon = document.createElement('img');
        icon.src = DOKU_BASE + 'lib/plugins/supa/supa.png';
        icon.title = LANG['plugins']['supa']['upload_button_hint'];
        icon.alt = LANG['plugins']['supa']['upload_button_alt'];
        icon.style.cursor = 'pointer';
        icon.onclick = function () {
            uploadForm.style.display = 'none';
            supaDiv.style.display = '';
        };

        uploadForm.appendChild(icon);

        pastebutton = $('supa__pastebutton');
        pastebutton.onclick = function (ev) {
            supa_handler.pasteButtonHandler(ev);
        };

        uploadbutton = $('supa__uploadbutton');
        uploadbutton.onclick = function (ev) {
            supa_handler.uploadButtonHandler(ev);
        };

        return true;
    },

    buildElements: function () {
        if (!supa_handler.createSupaApplet()) {
            // we're not on the media manager or something strange happened
            return;
        }

        if (!supa_handler.createSupaButton()) {
            // we're not on the media manager or something strange happened
            return;
        }
    },

    init: function (sectok, preview_scaler, preview_width, preview_height, namespace, default_filename) {
        supa_handler.preview_scaler = preview_scaler;
        supa_handler.preview_width = preview_width;
        supa_handler.preview_height = preview_height;
        supa_handler.sectok = sectok;
        supa_handler.namespace = namespace;
        supa_handler.default_filename = default_filename;

        supa_handler.buildElements();

        // okay... this is eveil but I don't have any idea how to resolve this
        // If the user clicks on a namespace link the page is refreshed via
        // AJAX (.e.: media_manager.list()). But: javascript is not executed
        // in this process so we need a way to re-add the button/applet
        var origList = media_manager.list;
        media_manager.list = function (event, link) {
            origList(event, link);
            supa_handler.buildElements();


        };
    },

    initSupaUpload: function () {
        // this is just a stub to not make dokuwiki crash in case the
        // user forgot to remove the legacy source patch
    }


};

