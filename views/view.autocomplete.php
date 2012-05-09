<?php
/**
 * Author: Jens Scherbl
 * Date: 12-03-04
 */

// The class name must be 'SBLPView_[filename - view. and .php (ucfirst)]':
Class SBLPView_Autocomplete
{
    /**
     * Return the name of this view
     * @return string
     */
    public function getName()
    {
        return __('Autocomplete');
    }

    /**
     * This function generates the view on the publish page
     * @param $viewWrapper
	 *  The XMLElement wrapper in which the view is placed
     * @param $fieldname
	 *  The name of the field
     * @param $options
	 *  The options
     * @param $parent
	 *  The parent element (this is the Field itself)
     * @return void
     */
    public function generateView(&$viewWrapper, $fieldname, $options, $parent)
    {
        // Add the selectbox:
        $viewWrapper->appendChild(
            Widget::Select($fieldname, $options, ($parent->get('allow_multiple_selection') == 'yes' ? array(
                'multiple' => 'multiple') : NULL
            ))
        );
        
        $viewWrapper->appendChild(new XMLElement('input', null, array('type' => 'text', 'id' => 'sblp_autocomplete' . $parent->get('id'))));

        // Set the viewname: this is required by the javascript-functions to edit or delete entries.
        $viewName = 'sblp-view-'.$parent->get('id');

        // Show checkboxes:
        $checkboxes = new XMLElement('div', null, array('class'=>'sblp-checkboxes'));
        foreach($options as $optGroup)
        {
			$container = new XMLElement('div', null, array('class'=>'container'));
            if(isset($optGroup['label']))
            {
				$container->appendChild(new XMLElement('h3', $optGroup['label'].' <em>(drag to reorder)</em>'));

				// Show created / hide others:
				$label = new XMLElement('span', null, array('class'=>'hide-others'));
				$checked = $parent->get('show_created') == 1 ? array('checked'=>'checked') : array();
				$input = Widget::Input('show_created', null, 'checkbox', $checked);
				$label->setValue(__('%s hide others', array($input->generate())));
				$container->appendChild($label);

                // Set the sectionname: this is required by the javascript-functions to edit or delete entries.
                $sectionName = General::createHandle($optGroup['label']);
                // In case of no multiple and not required:
                if($parent->get('allow_multiple_selection') == 'no' && $parent->get('required') == 'no')
                {
                    $label = Widget::Label('<em>'.__('Select none').'</em>', Widget::Input('sblp-checked-'.$parent->get('id'), '0', 'radio'));
					$container->appendChild($label);
                }
                foreach($optGroup['options'] as $option)
                {
                    $id       = $option[0];
					$value    = strip_tags(html_entity_decode($option[2]));
                    // Now this is where the name of the item, including the edit- and delete buttons are rendered:
                    // Please note that the edit- and delete-buttons use javascript functions provided by sbl+ to handle
                    // this functionality. This is done to make sure this extension uses as much native Symphony
                    // functionality as possible:
					$label = Widget::Label();
                    if($parent->get('allow_multiple_selection') == 'yes')
                    {
                        $input = Widget::Input('sblp-checked-'.$parent->get('id').'[]', (string)$id, 'checkbox');
                    } else {
						$input = Widget::Input('sblp-checked-'.$parent->get('id'), (string)$id, 'radio');
                    }
					$label->setValue(__('%s <span class="text">%s</span>', array($input->generate(), $value)));
					$label->setAttribute('title', $value);
					$label->setAttribute('rel', $id);
                    $label->appendChild(new XMLElement('span', '
                        <a href="#" class="edit" onclick="sblp_editEntry(\''.$viewName.'\',\''.$sectionName.'\','.$id.'); return false;">Edit</a>
                        <a href="#" class="delete" onclick="sblp_deleteEntry(\''.$viewName.'\',\''.$sectionName.'\','.$id.'); return false;">Delete</a>',
                        array('class' => 'sblp-checkboxes-actions')
                    ));
					$container->appendChild($label);
                }
            }
			$checkboxes->appendChild($container);
        }

        $viewWrapper->appendChild($checkboxes);

        // CSS:
        $viewWrapper->appendChild(new XMLElement('style', '
            div.sblp-checkboxes { max-height: 332px; overflow-y: auto; overflow-x: hidden; border: 1px solid #ccc;
            	margin-top: 5px; position: relative; }
            div.sblp-checkboxes h3 { padding: 5px; }
            div.sblp-checkboxes h3 em { font-size: 10px; font-weight: normal; font-style: normal; }
            div.sblp-checkboxes label { margin: 0; padding: 5px; border-bottom: 1px solid #ccc; position: relative; }
            div.sblp-checkboxes label input { float: left; }
            div.sblp-checkboxes span.text { display: block; margin-left: 20px; }
            div.sblp-checkboxes label .sblp-checkboxes-actions { display: none; position: absolute; right: 0; top: 0;
            	background: #fff; padding: 5px;}
            div.sblp-checkboxes label:hover .sblp-checkboxes-actions { display: inline; }
            div.sblp-checkboxes span.hide-others { position: absolute; top: 5px; right: 5px; }
            #sblp-view-'.$parent->get('id').' select { display: none; }
        ', array('type'=>'text/css')));

		$createdIDs = $parent->getCreatedEntryIds();

        // Javascript should be placed inside an sblp_initview[$viewName]()-function, to make sure it gets executed whenever the view
        // reloads with AJAX. This happens when an entry gets added, edited or deleted:
        $viewWrapper->appendChild(new XMLElement('script', '
            sblp_initview["'.$viewName.'"] = function()
            {
                var $ = jQuery;
                var created = ['.implode(',', $createdIDs).'];
				var multiple = '.($parent->get('allow_multiple_selection') == 'yes' ? 'true' : 'false').';

                $("#'.$viewName.' select option:selected").each(function(){
                
                	var option = $("#'.$viewName.' div.sblp-checkboxes input[value=" + $(this).val() + "]");
                	
                	option.attr("checked", "checked");
                    option.parent().parent().parent().show();
                });
                
                $("#'.$viewName.' div.sblp-checkboxes input").change(function(e){
                    $("#'.$viewName.' select option").removeAttr("selected");
                    $("#'.$viewName.' input:checked").each(function(){
                        var id = $(this).val();
                        $("#'.$viewName.' select option[value=" + id + "]").attr("selected", "selected");
                    });
                });

				var options = [];
				
				$("#'.$viewName.' select option").each(function(i) {
					options[i] = { name: $(this).text(), id: $(this).attr("value") };
				});			

				$("#sblp_autocomplete' . $parent->get('id') . '").autocomplete(options, {
					multiple: true,
					matchContains: true,
					formatItem: function(row, i, max) {
						return row.name;
					},
					formatMatch: function(row, i, max) {
						return row.name;
					}
					}).result(function(event, data, formatted) {

						var option = $("#'.$viewName.' div.sblp-checkboxes input[value=" + data.id + "]");
						
						option.attr("checked", "checked");
	                    option.parent().parent().parent().show();
	                    
						$("#'.$viewName.' select option[value=" + data.id + "]").attr("selected", "selected");
						
						jQuery(this).val("");
				});

                if(multiple)
                {
                    // Load the sorting order-state:
                    sblp_loadSorting("'.$viewName.'", "#'.$viewName.' label", "rel");

                    $("#'.$viewName.' div.sblp-checkboxes div.container").sortable({items: "label", update: function(){
                        // Update the option list according to the label items:
                        sblp_sortItems("'.$viewName.'", $("#'.$viewName.' label"), "rel");
                    }});
		            $("#'.$viewName.'").disableSelection();
                }

                // Hide others:
                $("#'.$viewName.' input[name=show_created]").change(function(){
                	if($(this).attr("checked"))
                	{
                		// Only show the created items:
                		$("#'.$viewName.' label").hide();
                		$("#'.$viewName.' label:has(input:checked)").show();
                		for(var i in created)
                		{
                			$("#'.$viewName.' label[rel="+created[i]+"]").show();
                		}
                	} else {
                		// Show everything:
                		$("#'.$viewName.' label").show();
                	}
                }).change();

            };
        ', array('type'=>'text/javascript')));
        
		// Append styles for view

        Administration::instance()->Page->addScriptToHead(URL.'/extensions/selectbox_link_field_plus/assets/view.autocomplete.js');
        Administration::instance()->Page->addStylesheetToHead(URL.'/extensions/selectbox_link_field_plus/assets/view.autocomplete.css');        
    }
}