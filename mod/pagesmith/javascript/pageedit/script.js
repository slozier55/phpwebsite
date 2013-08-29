var block_id = 0;
var page_id = 0;
var section_id = 0;
var current_block;
$(document).ready(function() {
    var editor = CKEDITOR.replace('block-edit-textarea');
    initializeDialog(editor);
    initializePageTitleEdit();
    editBlock(editor);
    $.fn.powerTip.defaults.followMouse = 'true';
    $.fn.powerTip.defaults.offset = '10';
    $('.block-edit').powerTip();
});
function editBlock(editor)
{
    $('.block-edit').click(function() {
        current_block = $(this);
        block_id = $(this).data('block-id');
        page_id = $(this).data('page-id');
        section_id = $(this).attr('id');
        $.get('index.php',
                {'module': 'pagesmith',
                    'aop': 'block_info',
                    'pid': page_id,
                    'bid': block_id,
                    'section_id': section_id
                },
        function(data) {
            editor.setData(data);
            $('#block-edit-popup').dialog('open');
            $('.ui-dialog').before('<div id="overlay" style="position: fixed ;width : 100%; height: 100%;background-color:none" class="ui-widget-overlay" />');
        }
        );
    });
}

function initializePageTitleEdit()
{
    $('#page-title-edit').click(function() {
        $('#page-title-input').val($('#page-title-edit').html());
        $('#title-edit-popup').dialog('open');
    });
}

function initializeDialog(editor)
{
    $('#block-edit-popup').dialog(
            {
                position: {my: 'center', at: 'center', of: this},
                autoOpen: false,
                width: 650,
                title: 'Edit text area',
                buttons: [{text: "Save",
                        click: function() {
                            updateBlock(editor);
                            $(this).dialog('close');
                            $('#overlay').remove();
                        }
                    }],
                close: function() {
                    $('#overlay').remove();
                }
            }
    );
    $('#title-edit-popup').dialog(
            {
                position: {my: 'center', at: 'center', of: this},
                autoOpen: false,
                width: 650,
                title: 'Edit page title',
                buttons: [{text: "Save",
                        click: function() {
                            var title_input = $('#page-title-input').val();
                            title_input = title_input.replace('/[<>]/gi', '');
                            $('#page-title-hidden').val(title_input);
                            $('#page-title-edit').html(title_input);
                            $('#page-title-edit').css('color', 'inherit');
                            $(this).dialog('close');
                            $('#overlay').remove();
                        }
                    }],
                close: function() {
                    $('#overlay').remove();
                }
            }
    );

}

function updateBlock(editor) {
    content = editor.getData();
    $.post('index.php',
            {
                'module': 'pagesmith',
                'aop': 'save_block',
                'pid': page_id,
                'bid': block_id,
                'content': content,
                'section_id': section_id
            }, function(data) {
        if (content === '') {
            content = '[Click to edit]';
        }
        current_block.html(content);
    });
}