// Authoring UI for mod_vidinteractivo.

define(['core/ajax', 'core/notification', 'core/templates', 'core/modal', 'mod_vidinteractivo/videoadapter'],
function(Ajax, Notification, Templates, Modal, VideoAdapter) {

    'use strict';

    var TYPES = [
        {value: 'html', label: 'Contenido HTML / texto'},
        {value: 'capture', label: 'Pausa informativa'},
        {value: 'multiplechoice', label: 'Opcion multiple (Bloque)'},
        {value: 'truefalse', label: 'Verdadero / Falso (Bloque)'},
        {value: 'imageselection', label: 'Seleccion sobre imagen'},
        {value: 'dragdrop', label: 'Arrastrar y soltar'},
        {value: 'shortanswer', label: 'Respuesta corta'}
    ];

    var normalizeType = function(type) {
        var aliases = {
            question: 'multiplechoice',
            flashcard: 'capture',
            true_false: 'truefalse',
            image_selection: 'imageselection',
            drag_drop: 'dragdrop',
            short_answer: 'shortanswer'
        };
        return aliases[type] || type;
    };

    var getInteractionName = function(type) {
        var names = {
            multiplechoice: 'Opcion multiple',
            truefalse: 'Verdadero / Falso',
            imageselection: 'Seleccion de imagen',
            dragdrop: 'Arrastrar y soltar',
            shortanswer: 'Respuesta corta',
            html: 'HTML',
            capture: 'Pausa Informativa'
        };
        return names[type] || type;
    };

    var splitLines = function(value) {
        return (value || '').split(/\r?\n/).map(function(line) {
            return line.trim();
        }).filter(Boolean);
    };

    var parseAreas = function(value) {
        return splitLines(value).map(function(line) {
            var parts = line.split(',').map(function(item) {
                return item.trim();
            });
            return {
                x: parseFloat(parts[0]) || 0,
                y: parseFloat(parts[1]) || 0,
                width: parseFloat(parts[2]) || 0,
                height: parseFloat(parts[3]) || 0,
                correct: parts[4] === undefined ? true : /^(1|true|si|sí|yes)$/i.test(parts[4])
            };
        });
    };

    var parseMapping = function(value) {
        var mapping = {};
        splitLines(value).forEach(function(line) {
            var parts = line.split('=');
            if (parts.length === 2) {
                mapping[parts[0].trim()] = parts[1].trim();
            }
        });
        return mapping;
    };

    var mappingToText = function(mapping) {
        return Object.keys(mapping || {}).map(function(key) {
            return key + '=' + mapping[key];
        }).join('\n');
    };

    var areasToText = function(areas) {
        return (areas || []).map(function(area) {
            return [area.x || 0, area.y || 0, area.width || 0, area.height || 0, area.correct ? 1 : 0].join(',');
        }).join('\n');
    };

    var escapeHtml = function(value) {
        return String(value || '').replace(/[&<>"']/g, function(ch) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[ch];
        });
    };

    var Timeline = function(videoSelector, cmid) {
        this.video = new VideoAdapter(videoSelector);
        this.cmid = cmid;
        this.markers = [];
        this.bindAddButton();
        this.loadMarkers();
    };

    Timeline.prototype.bindAddButton = function() {
        var self = this;
        var btn = document.getElementById('vidinteractivo-add-marker');
        if (btn) {
            btn.addEventListener('click', function() {
                self.openAuthoringModal();
            });
        }
    };

    Timeline.prototype.getCurrentTime = function() {
        return Math.floor(this.video ? this.video.currentTime : 0);
    };

    Timeline.prototype.loadMarkers = function() {
        var self = this;
        return Ajax.call([{
            methodname: 'mod_vidinteractivo_get_interactions',
            args: {cmid: this.cmid}
        }])[0].done(function(response) {
            self.markers = response.map(function(marker) {
                marker.type = normalizeType(marker.type);
                return marker;
            });
            self.renderTimelineMarkers();
            self.renderAuthoringPanel();
            return;
        }).fail(Notification.exception);
    };

    Timeline.prototype.emptyContentData = function() {
        return {
            html: '',
            capture: '',
            questions: [],
            imageurl: '',
            areas: '10,10,30,30,1',
            items: '',
            zones: '',
            mapping: '',
            answers: '',
            casesensitive: false,
            prompt: '' // For backward compatibility
        };
    };

    Timeline.prototype.contentForModal = function(marker) {
        var data = this.emptyContentData();
        if (!marker || !marker.content) {
            return data;
        }

        var parsed = {};
        try {
            parsed = JSON.parse(marker.content);
        } catch (e) {
            parsed = {};
        }

        data.html = parsed.html || '';
        data.capture = parsed.capture || parsed.message || '';
        data.imageurl = parsed.imageurl || parsed.image || '';
        data.areas = areasToText(parsed.areas || []);
        data.items = (parsed.items || []).join('\n');
        data.zones = (parsed.zones || []).join('\n');
        data.mapping = mappingToText(parsed.mapping || {});
        data.answers = (parsed.answers || []).join('\n');
        data.casesensitive = !!parsed.casesensitive;
        data.prompt = parsed.prompt || parsed.question || '';

        // Backward compatibility mapping for Multiple Choice and True/False
        if (parsed.questions && Array.isArray(parsed.questions)) {
            data.questions = parsed.questions;
        } else if (marker.type === 'multiplechoice' || marker.type === 'truefalse') {
            var q = {
                prompt: parsed.prompt || parsed.question || '',
            };
            if (marker.type === 'multiplechoice') {
                q.options = parsed.options || [];
                q.correctanswers = parsed.correctanswers || (parsed.correct !== undefined ? [parsed.correct] : [0]);
                q.multiple = !!parsed.multiple;
            } else if (marker.type === 'truefalse') {
                q.correct = parsed.correct !== false;
            }
            data.questions = [q];
        }

        return data;
    };

    Timeline.prototype.openAuthoringModal = function(existingMarker) {
        var self = this;
        var marker = existingMarker || {};
        var timestamp = existingMarker ? parseInt(marker.timestamp, 10) : this.getCurrentTime();
        var type = normalizeType(marker.type || 'multiplechoice');
        var interactionid = existingMarker ? marker.id : 0;

        if (this.video) {
            this.video.pause();
        }

        var typesContext = TYPES.map(function(t) {
            return {value: t.value, label: t.label, selected: type === t.value};
        });

        var contentData = this.contentForModal(existingMarker);

        var endMin = '';
        var endSec = '';
        if (marker.timeend) {
            endMin = Math.floor(marker.timeend / 60);
            endSec = marker.timeend % 60;
        }

        Templates.render('mod_vidinteractivo/add_interaction_modal', {
            timestamp_min: Math.floor(timestamp / 60),
            timestamp_sec: timestamp % 60,
            timeend_min: endMin,
            timeend_sec: endSec,
            timeend: marker.timeend || 0,
            title: marker.title || '',
            types: typesContext,
            contentData: contentData,
            maxscore: marker.maxscore === undefined ? 1 : marker.maxscore,
            attemptsallowed: marker.attemptsallowed === undefined ? 0 : marker.attemptsallowed,
            penalty: marker.penalty || 0,
            required: marker.required === undefined ? true : marker.required,
            pause: marker.pause === undefined ? true : marker.pause
        }).then(function(html) {
            return Modal.create({
                title: existingMarker ? 'Editar interaccion' : 'Agregar interaccion',
                body: html,
                large: true,
                removeOnClose: true
            });
        }).then(function(modal) {
            modal.show();
            var root = modal.getRoot()[0];
            var typeSelect = root.querySelector('[name="type"]');
            var sections = root.querySelectorAll('.interaction-type-section');

            var updateVisibility = function() {
                sections.forEach(function(section) {
                    section.style.display = section.id === 'sec-' + typeSelect.value ? 'block' : 'none';
                });
            };
            typeSelect.addEventListener('change', updateVisibility);
            updateVisibility();

            // Setup Question Blocks
            var tplMcqQ = root.querySelector('#tpl-mcq-question').innerHTML;
            var tplMcqOpt = root.querySelector('#tpl-mcq-option').innerHTML;
            var tplTfQ = root.querySelector('#tpl-tf-question').innerHTML;
            
            var mcqContainer = root.querySelector('#mcq-questions-container');
            var tfContainer = root.querySelector('#tf-questions-container');

            var createMcqOption = function(container, text, isCorrect) {
                var div = document.createElement('div');
                div.innerHTML = tplMcqOpt;
                var row = div.firstElementChild;
                row.querySelector('.mcq-option-text').value = text || '';
                row.querySelector('.mcq-option-correct').checked = !!isCorrect;
                
                row.querySelector('.btn-remove-option').addEventListener('click', function() {
                    row.remove();
                });
                container.appendChild(row);
            };

            var createMcqQuestion = function(qData) {
                var div = document.createElement('div');
                div.innerHTML = tplMcqQ;
                var block = div.firstElementChild;
                block.querySelector('.mcq-prompt').value = qData.prompt || '';
                block.querySelector('.mcq-multiple').checked = !!qData.multiple;
                
                var optsContainer = block.querySelector('.mcq-options-container');
                var options = qData.options || ['', ''];
                var correct = qData.correctanswers || [0];
                
                options.forEach(function(opt, idx) {
                    createMcqOption(optsContainer, opt, correct.includes(idx));
                });

                block.querySelector('.btn-add-mcq-option').addEventListener('click', function() {
                    createMcqOption(optsContainer, '', false);
                });

                block.querySelector('.btn-remove-question').addEventListener('click', function() {
                    if (confirm('¿Eliminar pregunta?')) block.remove();
                });
                
                mcqContainer.appendChild(block);
            };

            var createTfQuestion = function(qData) {
                var div = document.createElement('div');
                div.innerHTML = tplTfQ;
                var block = div.firstElementChild;
                block.querySelector('.tf-prompt').value = qData.prompt || '';
                block.querySelector('.tf-correct').value = qData.correct !== false ? 'true' : 'false';
                
                block.querySelector('.btn-remove-question').addEventListener('click', function() {
                    if (confirm('¿Eliminar pregunta?')) block.remove();
                });
                
                tfContainer.appendChild(block);
            };

            // Load existing questions
            if (contentData.questions && contentData.questions.length > 0) {
                if (type === 'multiplechoice') {
                    contentData.questions.forEach(createMcqQuestion);
                } else if (type === 'truefalse') {
                    contentData.questions.forEach(createTfQuestion);
                }
            } else {
                createMcqQuestion({});
                createTfQuestion({});
            }

            root.querySelector('#btn-add-mcq-question').addEventListener('click', function() {
                createMcqQuestion({});
            });
            root.querySelector('#btn-add-tf-question').addEventListener('click', function() {
                createTfQuestion({});
            });

            // Image Selection click mapping
            var imageUrlInput = root.querySelector('[name="image-url"]');
            var imagePreview = root.querySelector('#image-selection-preview');
            var imagePreviewContainer = root.querySelector('#image-selection-preview-container');
            var imageAreasInput = root.querySelector('[name="image-areas"]');
            var boxesContainer = root.querySelector('#image-boxes-container');
            var areasListContainer = root.querySelector('#image-areas-list');
            var tplImageArea = root.querySelector('#tpl-image-area-item') ? root.querySelector('#tpl-image-area-item').innerHTML : '';

            var imageAreasData = parseAreas(imageAreasInput ? imageAreasInput.value : '');
            var isDrawing = false;
            var startX = 0, startY = 0;
            var currentBox = null;

            var renderAreasUI = function() {
                if (!boxesContainer) return;
                boxesContainer.innerHTML = '';
                if (areasListContainer) areasListContainer.innerHTML = '';
                
                imageAreasData.forEach(function(area, index) {
                    var box = document.createElement('div');
                    box.style.position = 'absolute';
                    box.style.left = area.x + '%';
                    box.style.top = area.y + '%';
                    box.style.width = area.width + '%';
                    box.style.height = area.height + '%';
                    box.style.border = area.correct ? '2px solid #2ecc71' : '2px solid #e74c3c';
                    box.style.backgroundColor = area.correct ? 'rgba(46, 204, 113, 0.3)' : 'rgba(231, 76, 60, 0.3)';
                    box.style.pointerEvents = 'none';
                    
                    var label = document.createElement('span');
                    label.textContent = (index + 1);
                    label.style.position = 'absolute';
                    label.style.top = '0';
                    label.style.left = '0';
                    label.style.background = area.correct ? '#2ecc71' : '#e74c3c';
                    label.style.color = 'white';
                    label.style.padding = '0 4px';
                    label.style.fontSize = '12px';
                    box.appendChild(label);
                    boxesContainer.appendChild(box);

                    if (areasListContainer && tplImageArea) {
                        var div = document.createElement('div');
                        div.innerHTML = tplImageArea;
                        var item = div.firstElementChild;
                        item.querySelector('.area-label').textContent = 'Área ' + (index + 1);
                        item.querySelector('.area-correct').checked = area.correct;
                        
                        item.querySelector('.area-correct').addEventListener('change', function(e) {
                            imageAreasData[index].correct = e.target.checked;
                            renderAreasUI();
                            updateAreasInput();
                        });
                        
                        item.querySelector('.btn-remove-area').addEventListener('click', function() {
                            imageAreasData.splice(index, 1);
                            renderAreasUI();
                            updateAreasInput();
                        });
                        
                        areasListContainer.appendChild(item);
                    }
                });
            };

            var updateAreasInput = function() {
                if (imageAreasInput) imageAreasInput.value = areasToText(imageAreasData);
            };

            if (imageUrlInput && imagePreview) {
                var updatePreview = function() {
                    if (imageUrlInput.value) {
                        imagePreview.src = imageUrlInput.value;
                        imagePreviewContainer.style.display = 'block';
                        setTimeout(renderAreasUI, 100);
                    } else {
                        imagePreviewContainer.style.display = 'none';
                    }
                };
                imageUrlInput.addEventListener('change', updatePreview);
                imageUrlInput.addEventListener('blur', updatePreview);
                updatePreview();

                var fileInput = root.querySelector('#image-upload-file');
                if (fileInput) {
                    fileInput.addEventListener('change', function(e) {
                        var file = e.target.files[0];
                        if (!file) return;
                        if (!file.type.match('image.*')) {
                            alert('Por favor selecciona un archivo de imagen.');
                            return;
                        }
                        
                        var reader = new FileReader();
                        reader.onload = function(evt) {
                            var imgObj = new Image();
                            imgObj.onload = function() {
                                var canvas = document.createElement('canvas');
                                var ctx = canvas.getContext('2d');
                                var maxWidth = 1024;
                                var scale = 1;
                                
                                if (imgObj.width > maxWidth) {
                                    scale = maxWidth / imgObj.width;
                                }
                                
                                canvas.width = imgObj.width * scale;
                                canvas.height = imgObj.height * scale;
                                ctx.drawImage(imgObj, 0, 0, canvas.width, canvas.height);
                                
                                // Export as JPEG 80% quality
                                var dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                                imageUrlInput.value = dataUrl;
                                updatePreview();
                            };
                            imgObj.src = evt.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                }

                imagePreview.addEventListener('mousedown', function(e) {
                    var rect = imagePreview.getBoundingClientRect();
                    isDrawing = true;
                    startX = ((e.clientX - rect.left) / rect.width) * 100;
                    startY = ((e.clientY - rect.top) / rect.height) * 100;
                    
                    currentBox = document.createElement('div');
                    currentBox.style.position = 'absolute';
                    currentBox.style.border = '2px dashed #3498db';
                    currentBox.style.backgroundColor = 'rgba(52, 152, 219, 0.2)';
                    currentBox.style.pointerEvents = 'none';
                    currentBox.style.left = startX + '%';
                    currentBox.style.top = startY + '%';
                    currentBox.style.width = '0%';
                    currentBox.style.height = '0%';
                    boxesContainer.appendChild(currentBox);
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isDrawing || !currentBox) return;
                    var rect = imagePreview.getBoundingClientRect();
                    var currentX = ((e.clientX - rect.left) / rect.width) * 100;
                    var currentY = ((e.clientY - rect.top) / rect.height) * 100;
                    
                    currentX = Math.max(0, Math.min(100, currentX));
                    currentY = Math.max(0, Math.min(100, currentY));

                    var x = Math.min(startX, currentX);
                    var y = Math.min(startY, currentY);
                    var w = Math.abs(currentX - startX);
                    var h = Math.abs(currentY - startY);

                    currentBox.style.left = x + '%';
                    currentBox.style.top = y + '%';
                    currentBox.style.width = w + '%';
                    currentBox.style.height = h + '%';
                });

                document.addEventListener('mouseup', function(e) {
                    if (!isDrawing) return;
                    isDrawing = false;
                    
                    var rect = imagePreview.getBoundingClientRect();
                    var endX = ((e.clientX - rect.left) / rect.width) * 100;
                    var endY = ((e.clientY - rect.top) / rect.height) * 100;
                    
                    endX = Math.max(0, Math.min(100, endX));
                    endY = Math.max(0, Math.min(100, endY));

                    var x = Math.min(startX, endX);
                    var y = Math.min(startY, endY);
                    var w = Math.abs(endX - startX);
                    var h = Math.abs(endY - startY);

                    if (w > 2 && h > 2) { 
                        imageAreasData.push({
                            x: parseFloat(x.toFixed(2)),
                            y: parseFloat(y.toFixed(2)),
                            width: parseFloat(w.toFixed(2)),
                            height: parseFloat(h.toFixed(2)),
                            correct: true // Default correct
                        });
                        updateAreasInput();
                    }
                    renderAreasUI();
                });
            }

            // Short Answer
            var shortAnswersContainer = root.querySelector('#short-answers-container');
            var tplShortAnswer = root.querySelector('#tpl-short-answer') ? root.querySelector('#tpl-short-answer').innerHTML : '';
            var createShortAnswer = function(text) {
                if (!shortAnswersContainer || !tplShortAnswer) return;
                var div = document.createElement('div');
                div.innerHTML = tplShortAnswer;
                var item = div.firstElementChild;
                item.querySelector('.short-answer-input').value = text || '';
                item.querySelector('.btn-remove-answer').addEventListener('click', function() {
                    item.remove();
                });
                shortAnswersContainer.appendChild(item);
            };

            var shortAnswersList = splitLines(contentData.answers || '');
            if (shortAnswersList.length > 0) {
                shortAnswersList.forEach(function(ans) { createShortAnswer(ans); });
            } else {
                createShortAnswer('');
            }
            if (root.querySelector('#btn-add-short-answer')) {
                root.querySelector('#btn-add-short-answer').addEventListener('click', function() {
                    createShortAnswer('');
                });
            }

            // Drag and Drop
            var dragZonesContainer = root.querySelector('#drag-zones-container');
            var dragItemsContainer = root.querySelector('#drag-items-container');
            var tplDragZone = root.querySelector('#tpl-drag-zone') ? root.querySelector('#tpl-drag-zone').innerHTML : '';
            var tplDragItem = root.querySelector('#tpl-drag-item') ? root.querySelector('#tpl-drag-item').innerHTML : '';

            var updateDragItemDropdowns = function() {
                if (!dragZonesContainer || !dragItemsContainer) return;
                var zones = Array.prototype.slice.call(dragZonesContainer.querySelectorAll('.drag-zone-input')).map(function(input) {
                    return input.value.trim();
                }).filter(Boolean);

                dragItemsContainer.querySelectorAll('.drag-item-mapping').forEach(function(select) {
                    var currentVal = select.value;
                    select.innerHTML = '<option value="">Ninguna (Distractor)</option>';
                    zones.forEach(function(z) {
                        var opt = document.createElement('option');
                        opt.value = z;
                        opt.textContent = z;
                        select.appendChild(opt);
                    });
                    if (zones.indexOf(currentVal) !== -1) {
                        select.value = currentVal;
                    }
                });
            };

            var createDragZone = function(text) {
                if (!dragZonesContainer || !tplDragZone) return;
                var div = document.createElement('div');
                div.innerHTML = tplDragZone;
                var item = div.firstElementChild;
                var input = item.querySelector('.drag-zone-input');
                input.value = text || '';
                input.addEventListener('input', updateDragItemDropdowns);
                item.querySelector('.btn-remove-zone').addEventListener('click', function() {
                    item.remove();
                    updateDragItemDropdowns();
                });
                dragZonesContainer.appendChild(item);
            };

            var createDragItem = function(text, mappedZone) {
                if (!dragItemsContainer || !tplDragItem) return;
                var div = document.createElement('div');
                div.innerHTML = tplDragItem;
                var item = div.firstElementChild;
                item.querySelector('.drag-item-input').value = text || '';
                item.querySelector('.btn-remove-item').addEventListener('click', function() {
                    item.remove();
                });
                dragItemsContainer.appendChild(item);
                updateDragItemDropdowns();
                var select = item.querySelector('.drag-item-mapping');
                if (mappedZone && select.querySelector('option[value="' + mappedZone.replace(/"/g, '\\"') + '"]')) {
                    select.value = mappedZone;
                }
            };

            var dragZonesList = splitLines(contentData.zones || '');
            var dragItemsList = splitLines(contentData.items || '');
            var dragMappingObj = typeof contentData.mapping === 'string' ? parseMapping(contentData.mapping) : (contentData.mapping || {});

            if (dragZonesList.length > 0) {
                dragZonesList.forEach(function(z) { createDragZone(z); });
            } else {
                createDragZone('Zona 1');
            }

            if (dragItemsList.length > 0) {
                dragItemsList.forEach(function(itemText) { 
                    createDragItem(itemText, dragMappingObj[itemText] || '');
                });
            } else {
                createDragItem('Elemento 1', '');
            }

            if (root.querySelector('#btn-add-drag-zone')) {
                root.querySelector('#btn-add-drag-zone').addEventListener('click', function() {
                    createDragZone('');
                });
            }
            if (root.querySelector('#btn-add-drag-item')) {
                root.querySelector('#btn-add-drag-item').addEventListener('click', function() {
                    createDragItem('', '');
                });
            }

            root.querySelector('[data-action="cancel"]').addEventListener('click', function() {
                modal.hide();
            });

            root.querySelector('[data-action="save-interaction"]').addEventListener('click', function() {
                var selectedType = typeSelect.value;
                var config = self.buildConfig(root, selectedType);
                
                var min = parseInt(root.querySelector('[name="timestamp-min"]').value, 10) || 0;
                var sec = parseInt(root.querySelector('[name="timestamp-sec"]').value, 10) || 0;
                var totalSeconds = (min * 60) + sec;

                var endMinStr = root.querySelector('[name="timeend-min"]').value;
                var endSecStr = root.querySelector('[name="timeend-sec"]').value;
                var timeendVal = 0;
                if (endMinStr !== '' || endSecStr !== '') {
                    timeendVal = (parseInt(endMinStr, 10) || 0) * 60 + (parseInt(endSecStr, 10) || 0);
                }

                self.saveMarker(interactionid, {
                    timestamp: totalSeconds,
                    timeend: timeendVal,
                    type: selectedType,
                    title: root.querySelector('[name="interaction-title"]').value,
                    content: JSON.stringify(config),
                    maxscore: parseFloat(root.querySelector('[name="maxscore"]').value) || 0,
                    attemptsallowed: parseInt(root.querySelector('[name="attemptsallowed"]').value, 10) || 0,
                    penalty: parseFloat(root.querySelector('[name="penalty"]').value) || 0,
                    required: root.querySelector('[name="required"]').checked,
                    pause: root.querySelector('[name="pause"]').checked
                });
                modal.hide();
            });

            return;
        }).catch(Notification.exception);
    };

    Timeline.prototype.buildConfig = function(root, type) {
        if (type === 'html') {
            return {html: root.querySelector('[name="html-text"]').value};
        }
        if (type === 'capture') {
            return {capture: root.querySelector('[name="capture-text"]').value};
        }
        if (type === 'multiplechoice') {
            var mcqBlocks = root.querySelectorAll('.mcq-question-block');
            var questions = [];
            mcqBlocks.forEach(function(block) {
                var prompt = block.querySelector('.mcq-prompt').value;
                var options = [];
                var correct = [];
                var optionRows = block.querySelectorAll('.mcq-option-row');
                optionRows.forEach(function(row, idx) {
                    options.push(row.querySelector('.mcq-option-text').value);
                    if (row.querySelector('.mcq-option-correct').checked) {
                        correct.push(idx);
                    }
                });
                if (correct.length === 0) correct = [0]; // fallback
                questions.push({
                    prompt: prompt,
                    options: options,
                    correctanswers: correct,
                    multiple: block.querySelector('.mcq-multiple').checked
                });
            });
            return { questions: questions };
        }
        if (type === 'truefalse') {
            var tfBlocks = root.querySelectorAll('.tf-question-block');
            var questions = [];
            tfBlocks.forEach(function(block) {
                questions.push({
                    prompt: block.querySelector('.tf-prompt').value,
                    correct: block.querySelector('.tf-correct').value === 'true'
                });
            });
            return { questions: questions };
        }
        if (type === 'imageselection') {
            return {
                prompt: root.querySelector('[name="image-prompt"]').value,
                imageurl: root.querySelector('[name="image-url"]').value,
                areas: parseAreas(root.querySelector('[name="image-areas"]').value)
            };
        }
        if (type === 'dragdrop') {
            var items = [];
            var zones = [];
            var mapping = {};
            
            root.querySelectorAll('.drag-zone-input').forEach(function(input) {
                var val = input.value.trim();
                if (val && zones.indexOf(val) === -1) zones.push(val);
            });
            
            root.querySelectorAll('.drag-item-input').forEach(function(input) {
                var val = input.value.trim();
                var select = input.closest('.bg-light').querySelector('.drag-item-mapping');
                if (val && items.indexOf(val) === -1) {
                    items.push(val);
                    if (select && select.value) {
                        mapping[val] = select.value;
                    }
                }
            });

            return {
                prompt: root.querySelector('[name="drag-prompt"]').value,
                items: items,
                zones: zones,
                mapping: mapping
            };
        }
        if (type === 'shortanswer') {
            var answers = [];
            root.querySelectorAll('.short-answer-input').forEach(function(input) {
                var val = input.value.trim();
                if (val && answers.indexOf(val) === -1) answers.push(val);
            });
            return {
                prompt: root.querySelector('[name="short-prompt"]').value,
                answers: answers,
                casesensitive: root.querySelector('[name="short-casesensitive"]').checked
            };
        }
        return {};
    };

    Timeline.prototype.saveMarker = function(interactionid, marker) {
        var self = this;
        return Ajax.call([{
            methodname: 'mod_vidinteractivo_save_interaction',
            args: Object.assign({cmid: this.cmid, interactionid: interactionid, visible: true}, marker)
        }])[0].done(function() {
            self.loadMarkers();
            return;
        }).fail(Notification.exception);
    };

    Timeline.prototype.deleteMarker = function(interactionid) {
        var self = this;
        if (!confirm('Seguro que deseas eliminar esta interaccion? Tambien se eliminaran sus respuestas.')) {
            return;
        }
        return Ajax.call([{
            methodname: 'mod_vidinteractivo_delete_interaction',
            args: {cmid: this.cmid, interactionid: interactionid}
        }])[0].done(function() {
            self.loadMarkers();
            return;
        }).fail(Notification.exception);
    };

    Timeline.prototype.renderTimelineMarkers = function() {
        var bar = document.querySelector('.vidinteractivo-progressbar');
        if (!bar || !this.video || isNaN(this.video.duration) || this.video.duration === 0) {
            var self = this;
            setTimeout(function() {
                self.renderTimelineMarkers();
            }, 500);
            return;
        }
        bar.innerHTML = '';
        var duration = this.video.duration;
        this.markers.forEach(function(marker) {
            if (!marker.visible) {
                return;
            }
            var el = document.createElement('span');
            el.className = 'vidinteractivo-marker marker-' + marker.type;
            el.title = marker.type + ' @ ' + marker.timestamp + 's';
            el.style.left = (marker.timestamp / duration * 100) + '%';
            bar.appendChild(el);
        });
    };

    Timeline.prototype.renderAuthoringPanel = function() {
        var panel = document.getElementById('vidinteractivo-authoring-list');
        if (!panel) {
            return;
        }
        var self = this;
        if (!this.markers.length) {
            panel.innerHTML = '<div class="alert alert-info text-center py-3">No hay interacciones configuradas.</div>';
            return;
        }

        panel.innerHTML = '';
        var table = document.createElement('table');
        table.className = 'table table-hover table-striped align-middle';
        table.innerHTML = '<thead><tr><th>Tiempo</th><th>Tipo</th><th>Detalle</th><th>Puntos</th><th>Acciones</th></tr></thead><tbody></tbody>';
        var tbody = table.querySelector('tbody');

        this.markers.forEach(function(marker) {

            var parsed = {};
            try {
                parsed = JSON.parse(marker.content || '{}');
            } catch (e) {
                parsed = {};
            }
            var typeLabel = TYPES.find(function(t) {
                return t.value === marker.type;
            });
            var min = Math.floor(marker.timestamp / 60);
            var sec = marker.timestamp % 60;
            var timeStr = min + ':' + (sec < 10 ? '0' : '') + sec;
            
            var desc = marker.title || parsed.prompt || parsed.question || parsed.capture || parsed.html || '';
            if (parsed.questions && parsed.questions.length > 0) {
                desc = parsed.questions[0].prompt + ' (+' + (parsed.questions.length - 1) + ' más)';
            }
            
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + timeStr + '</strong></td>' +
                '<td><span class="badge badge-info type-badge-' + escapeHtml(marker.type) + '">' + escapeHtml(typeLabel ? typeLabel.label : marker.type) + '</span></td>' +
                '<td><small>' + escapeHtml(desc).substring(0, 100) + '</small></td>' +
                '<td>' + escapeHtml(marker.maxscore) + '</td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' + marker.id + '"><i class="fa fa-pencil"></i></button> ' +
                    '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="' + marker.id + '"><i class="fa fa-trash"></i></button>' +
                '</td>';
            tr.querySelector('.btn-edit').addEventListener('click', function() {
                self.openAuthoringModal(marker);
            });
            tr.querySelector('.btn-delete').addEventListener('click', function() {
                self.deleteMarker(marker.id);
            });
            tbody.appendChild(tr);
        });

        panel.appendChild(table);
    };

    return {
        init: function(videoSelector, cmid) {
            return new Timeline(videoSelector, cmid);
        }
    };
});
