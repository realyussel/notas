//constructor
var BaseEditor = function() {
    this.saveButton = null;
    this.saveImage = null;
    this.editor = null;
    this.unsavedContent = false;
    this.currentlySaving = false;
    this.cancelKeypress = false; //workaround for Firefox bug
    this.isCtrl = false;
    this.addButton = null;
    this.addStartButton = null;
    this.modalHeader = null;
    this.menuHeader = null;
};
//prototype
BaseEditor.prototype = {
    init: function() {
        this.saveButton = document.getElementById('save-button');
        this.saveImage = document.getElementById('save-button').querySelector('img'); //TODO fix this bug: point to the <img> inside ID 'save-button'
        this.editor = document.getElementById('editor');
        
        /// Markdown buttons
        
        this.addButton = document.querySelectorAll('.add-btn');
        this.addStartButton = document.querySelectorAll('.add-start-btn');
        this.modalHeader = document.getElementById('modal-header');
        this.menuHeader = document.getElementById('menu-header');
        
        /*** EVENTS */

        document.addEventListener('input', function(e) {
            this.setUnsavedStatus.call(this, true);
            this.textareaFitToContent.call(this);
        }.bind(this));
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.keyCode == 'S'.charCodeAt(0)) {
                e.preventDefault();
                if (this.unsavedContent) {
                    this.cancelKeypress = true;
                    this.saveNote.call(this);
                }
            } else if (e.ctrlKey && e.keyCode == 'B'.charCodeAt(0)) {
                this.addString.call(this, 'bold');
            } else if (e.ctrlKey && e.keyCode == 'I'.charCodeAt(0)) {
                this.addString.call(this, 'italic');
            } else if (e.ctrlKey && e.keyCode == 'T'.charCodeAt(0)) {
                this.addString.call(this, 'strike');
            }
        }.bind(this));
        // Tamaño del editor
        /* var autoExpand = function (field) {
            // Reset field height
            field.style.height = 'inherit';
            // Get the computed styles for the element
            var computed = window.getComputedStyle(field);
            // Calculate the height
            var height = parseInt(computed.getPropertyValue('border-top-width'), 10)
                         + parseInt(computed.getPropertyValue('padding-top'), 10)
                         + field.scrollHeight
                         + parseInt(computed.getPropertyValue('padding-bottom'), 10)
                         + parseInt(computed.getPropertyValue('border-bottom-width'), 10);
            field.style.height = height + 'px';
        };
        document.addEventListener('input', function (e) {
            if (e.target.tagName.toLowerCase() !== 'textarea') return;
            autoExpand(e.target);
        }, false);
        */
        // Tabulaciones dentro del editor
        document.addEventListener('keydown', function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode == 9) {
                e.preventDefault();
                var start = this.editor.selectionStart;
                var end = this.editor.selectionEnd;
                // set textarea value to: text before caret + tab + text after caret
                this.editor.value = this.editor.value.substring(0, start) + "   " + this.editor.value.substring(end);
                // Uso espacios en ves de la tabulación "\t"
                // put caret at right position again
                this.editor.selectionStart = this.editor.selectionEnd = start + 3;
            }
        }.bind(this));
        /* Modal Header */
        this.modalHeader.addEventListener('click', function(e) {
            this.toggleModal.call(this);
            this.addButton.classList.add('disabled');
            this.addStartButton.classList.add('disabled');
            e.preventDefault();
        }.bind(this));
        document.addEventListener('click', function(e) {
            var target = e.target;
            if (!$(target).is('.modal-menu li') && !$(target).siblings().is('.modal-menu')) {
                this.modalHeader.classList.remove('active');
                this.menuHeader.style.display = 'none';
            }
        }.bind(this));

        /**
         * Workaround for Firefox bug:
         * e.preventDefault(); and e.stopPropagation(); won't suffice in the keydown
         * event, and Firefox will still propagate to keypress in a specific case
         * where some non-basic code is executed during the keydown handler..
         */

        document.addEventListener('keypress', function(e) {
            if (this.cancelKeypress === true) {
                e.preventDefault();
                this.cancelKeypress = false;
            }
        }.bind(this));
        //auto save every 30 seconds
        setInterval(function() {
            if (this.unsavedContent && !this.currentlySaving) {
                this.saveNote.call(this);
            }
        }.bind(this), 30000);
        //click on save button
        this.saveButton.addEventListener('click', function(e) {
            if (this.unsavedContent)
                this.saveNote.call(this);
            e.preventDefault();
        }.bind(this));
        //avoid leaving page without saving
        window.addEventListener('beforeunload', function(e) {
            this.checkIsUnsaved.call(this, e);
        }.bind(this));
        this.customInit.call(this);
        this.addButton.forEach(el => el.addEventListener('click', function(e) {
            if (!e.currentTarget.classList.contains("disabled")) {
                var action = e.currentTarget.getAttribute('data-type');
                this.addString.call(this, action);
                e.preventDefault();
            }
        }.bind(this)));
        this.addStartButton.forEach(el => el.addEventListener('click', function(e) {
            if (!e.currentTarget.classList.contains("disabled")) {
                var action = e.currentTarget.getAttribute('data-type');
                this.addStartString.call(this, action);
                e.preventDefault();
            }
        }.bind(this)));
    },
    toggleModal: function(e) {
        if (this.modalHeader.className == 'btn btn-light active') {
            this.modalHeader.classList.remove('active');
            this.menuHeader.style.display = 'none';
        } else {
            this.modalHeader.classList.add('active');
            this.menuHeader.style.display = 'block';
        }
    },
    matchString: function(start, text) {
        this.editor.selectionStart = start;
        this.editor.selectionEnd = start + text.length;
        this.editor.blur();
        this.editor.focus();
    },
    addStartString: function(buttonType) {
        var leftTag = '';
        switch (buttonType) {
            case 'h1':
                leftTag = '# ';
                break;
            case 'h2':
                leftTag = '## ';
                break;
            case 'h3':
                leftTag = '### ';
                break;
            case 'h4':
                leftTag = '#### ';
                break;
            case 'h5':
                leftTag = '##### ';
                break;
            case 'h6':
                leftTag = '###### ';
                break;
            case 'quote':
                leftTag = '> ';
                break;
        }
        var text = this.editor.value;
        if (this.editor.selectionStart != undefined) {
            var startPos = this.editor.selectionStart;
            var endPos = this.editor.selectionEnd;
            var selectedText = text.substring(startPos, endPos);
            // Hallar el principio de la linea
            var beginning = 0;
            var overall = 0;
            var i = 0;
            var split = text.split("\n");
            while (overall <= startPos) {
                beginning = overall;
                overall += split[i].length + 1;
                i++;
            }
            this.editor.value = text.substring(0, beginning) + leftTag + text.substring(beginning, text.length);
            selectedText = text.substring(beginning, overall);
            this.setUnsavedStatus.call(this, true);
            // this.textareaFitToContent.call(this);
            startPos = beginning + leftTag.length;
            this.matchString.call(this, startPos, selectedText);
        }
    },
    addString: function(buttonType) {
        var leftTag = '';
        var rightTag = '';
        var innerText = '';
        switch (buttonType) {
            case 'italic':
                leftTag = rightTag = '_';
                innerText = 'Texto en cursivas';
                break;
            case 'bold':
                leftTag = rightTag = '**';
                innerText = 'Texto en negritas';
                break;
            case 'strike':
                leftTag = rightTag = '~~';
                innerText = 'Texto tachado';
                break;
            case 'code':
                leftTag = rightTag = '`';
                innerText = 'Código de cita';
                break;
            case 'code-block':
                leftTag = '```\n';
                rightTag = '\n```';
                innerText = 'Código de bloque';
                break;
            case 'highlighted':
                leftTag = rightTag = '==';
                innerText = 'Texto resaltado';
                break;
            case 'hotkey':
                leftTag = '[[';
                rightTag = ']]';
                innerText = 'Tecla de acceso rápido';
                break;
        }
        var text = this.editor.value;
        if (this.editor.selectionStart != undefined) {
            var startPos = this.editor.selectionStart;
            var endPos = this.editor.selectionEnd;
            var selectedText = text.substring(startPos, endPos);
            if (startPos != endPos) {
                this.editor.value = text.substring(0, startPos) + leftTag + selectedText + rightTag + text.substring(endPos, text.length);
            } else {
                this.editor.value = text.substring(0, startPos) + leftTag + innerText + rightTag + text.substring(endPos, text.length);
                selectedText = innerText;
            }
            this.setUnsavedStatus.call(this, true);
            // this.textareaFitToContent.call(this);
            startPos = startPos + leftTag.length;
            this.matchString.call(this, startPos, selectedText);
        }
    },
    customInit: function() {
        //markdown editor
        this.editor.setAttribute('contenteditable', true);
        this.textareaFitToContent.call(this);
        document.getElementById('preview-button').addEventListener('click', function(e) {
            var preview = null;
            var button = document.getElementById('preview-button');
            e.preventDefault();
            button.classList.toggle('active');
            if (button.classList.contains('active')) {
                //prepare preview container
                this.editor.style.display = 'none';
                preview = document.createElement('div');
                preview.setAttribute('id', 'preview');
                this.editor.parentNode.insertBefore(preview, this.editor.nextSibling);
                //show a loading gif
                var loadingGif = document.createElement('img');
                loadingGif.setAttribute('src', 'tpl/img/feather/loader.svg');
                loadingGif.setAttribute('alt', 'Loading...');
                loadingGif.setAttribute('id', 'loadingGif');
                preview.appendChild(loadingGif);
                //send preview request to server
                var request = new XMLHttpRequest();
                var notebook = document.getElementById('notebookTitle').getAttribute('data-name');
                var item = document.getElementById('selected').getAttribute('data-path');
                request.open('GET', '?action=ajax&option=preview&nb=' + notebook + '&item=' + item, false);
                request.send();
                response = JSON.parse(request.responseText);
                //replace gif with the parsed note
                if (response !== false) {
                    preview.innerHTML = response;
                }
                // Deshabilitar botones
                this.addButton.forEach(el => el.classList.add('disabled'));
                this.addStartButton.forEach(el => el.classList.add('disabled'));
                this.modalHeader.classList.add('disabled');
            } else {
                this.editor.style.display = 'block';
                preview = document.getElementById('preview');
                preview.parentNode.removeChild(preview);
                // Habilitar botones
                this.addButton.forEach(el => el.classList.remove('disabled'));
                this.addStartButton.forEach(el => el.classList.remove('disabled'));
                this.modalHeader.classList.remove('disabled');
            }
        }.bind(this));
    },
    textareaFitToContent: function() {
        var lineHeight = window.getComputedStyle(this.editor).lineHeight;
        lineHeight = parseInt(lineHeight.substr(0, lineHeight.length - 2), 10);
        if (this.editor.clientHeight == this.editor.scrollHeight)
            this.editor.style.height = (lineHeight * 4) + 'px';
        if (this.editor.scrollHeight > this.editor.clientHeight) {
            this.editor.style.height = (this.editor.scrollHeight + lineHeight) + "px";
        }
    },
    saveNote: function() {
        this.currentlySaving = true;
        this.saveButton.setAttribute('title', 'Guardando...');
        this.changeImageFile.call(this, 'loading.gif');
        var text = this.editor.value;

        // Tienen 
        var notebook = document.getElementById('notebookTitle').getAttribute('data-name');
        var item = document.getElementById('selected').getAttribute('data-path');

        var data = new FormData();
        data.append('text', text);
        
        //send save request to server
        function ensureMdExtension(path) {
            if (!path.endsWith('.md')) {
                return path + '/index.md';
            }
            return path;
        }

        var request = new XMLHttpRequest();
        var method = '?action=ajax&option=save&nb=' + notebook + '&item=' + ensureMdExtension(item);
        request.open('POST', method, false);

        /*
        // Manejo de la respuesta
        request.onload = function() {
            if (request.status >= 200 && request.status < 300) {
                // La solicitud fue exitosa
                document.getElementById('response').innerText = request.responseText;
            } else {
                // La solicitud falló
                document.getElementById('response').innerText = 'Error: ' + request.status + ' ' + request.statusText;
            }
        };
        // Manejo de errores
        request.onerror = function() {
            document.getElementById('response').innerText = 'Error de red o solicitud fallida';
        };
        // Manejo de tiempo de espera
        request.ontimeout = function() {
             document.getElementById('response').innerText = 'La solicitud ha tardado demasiado en responder';
        };
        // Configurar el tiempo de espera (opcional)
        request.timeout = 5000; // Tiempo en milisegundos
        */

        try {
            request.send(data);
            response = JSON.parse(request.responseText);
            //the note was saved
            if (response === true) {
                this.setUnsavedStatus.call(this, false);
            } else {
                this.changeImageFile.call(this, 'exclamation.gif');
                this.saveButton.setAttribute('title', 'Error: no se pudo guardar esta nota.');
            }
        } catch (error) {
            this.saveButton.setAttribute('title', 'Error: al enviar la solicitud. ' + method);
            //document.getElementById('response').innerText = 'Error al enviar la solicitud: ' + error.message;
        }
        this.currentlySaving = false;
        return false;
    },
    checkIsUnsaved: function(e) {
        if (this.unsavedContent) {
            e.preventDefault();
            return "Hay contenido no guardado. ¿Aún deseas abandonar esta página?";
        }
    },
    setUnsavedStatus: function(status) {
        this.unsavedContent = status;
        if (this.unsavedContent) {
            this.saveButton.classList.remove('disabled');
            this.saveButton.classList.remove('btn-secondary');
            this.saveButton.classList.add('btn-primary');
            this.saveButton.setAttribute('title', 'Guardar cambios');
            this.changeImageFile.call(this, 'guardar.svg');
        } else {
            this.saveButton.classList.add('disabled');
            this.saveButton.classList.remove('btn-primary');
            this.saveButton.classList.add('btn-secondary');
            this.saveButton.setAttribute('title', 'Nada que salvar');
            this.changeImageFile.call(this, 'guardado.svg');
        }
    },
    changeImageFile: function(newFileName) {
        var dirPath = this.saveImage.getAttribute('src').substring(0, this.saveImage.getAttribute('src').lastIndexOf('/') + 1);
        this.saveImage.setAttribute('src', dirPath + newFileName);
    }
};