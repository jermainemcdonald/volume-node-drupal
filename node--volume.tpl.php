<?php

/**
 * @file
 * jrerbartik's theme implementation to display a volume node.
 * Created July 15, 2013 by Jermaine McDonald
 *
 * Available variables:
 * - $title: the (sanitized) title of the node.
 * - $content: An array of node items. Use render($content) to print them all,
 *   or print a subset such as render($content['field_example']). Use
 *   hide($content['field_example']) to temporarily suppress the printing of a
 *   given element.
 * - $user_picture: The node author's picture from user-picture.tpl.php.
 * - $date: Formatted creation date. Preprocess functions can reformat it by
 *   calling format_date() with the desired parameters on the $created variable.
 * - $name: Themed username of node author output from theme_username().
 * - $node_url: Direct URL of the current node.
 * - $display_submitted: Whether submission information should be displayed.
 * - $submitted: Submission information created from $name and $date during
 *   template_preprocess_node().
 * - $classes: String of classes that can be used to style contextually through
 *   CSS. It can be manipulated through the variable $classes_array from
 *   preprocess functions. The default values can be one or more of the
 *   following:
 *   - node: The current template type; for example, "theming hook".
 *   - node-[type]: The current node type. For example, if the node is a
 *     "Blog entry" it would result in "node-blog". Note that the machine
 *     name will often be in a short form of the human readable label.
 *   - node-teaser: Nodes in teaser form.
 *   - node-preview: Nodes in preview mode.
 *   The following are controlled through the node publishing options.
 *   - node-promoted: Nodes promoted to the front page.
 *   - node-sticky: Nodes ordered above other non-sticky nodes in teaser
 *     listings.
 *   - node-unpublished: Unpublished nodes visible only to administrators.
 * - $title_prefix (array): An array containing additional output populated by
 *   modules, intended to be displayed in front of the main title tag that
 *   appears in the template.
 * - $title_suffix (array): An array containing additional output populated by
 *   modules, intended to be displayed after the main title tag that appears in
 *   the template.
 *
 * Other variables:
 * - $node: Full node object. Contains data that may not be safe.
 * - $type: Node type; for example, story, page, blog, etc.
 * - $comment_count: Number of comments attached to the node.
 * - $uid: User ID of the node author.
 * - $created: Time the node was published formatted in Unix timestamp.
 * - $classes_array: Array of html class attribute values. It is flattened
 *   into a string within the variable $classes.
 * - $zebra: Outputs either "even" or "odd". Useful for zebra striping in
 *   teaser listings.
 * - $id: Position of the node. Increments each time it's output.
 *
 * Node status variables:
 * - $view_mode: View mode; for example, "full", "teaser".
 * - $teaser: Flag for the teaser state (shortcut for $view_mode == 'teaser').
 * - $page: Flag for the full page state.
 * - $promote: Flag for front page promotion state.
 * - $sticky: Flags for sticky post setting.
 * - $status: Flag for published status.
 * - $comment: State of comment settings for the node.
 * - $readmore: Flags true if the teaser content of the node cannot hold the
 *   main body content.
 * - $is_front: Flags true when presented in the front page.
 * - $logged_in: Flags true when the current user is a logged-in member.
 * - $is_admin: Flags true when the current user is an administrator.
 *
 * Field variables: for each field instance attached to the node a corresponding
 * variable is defined; for example, $node->body becomes $body. When needing to
 * access a field's raw values, developers/themers are strongly encouraged to
 * use these variables. Otherwise they will have to explicitly specify the
 * desired field language; for example, $node->body['en'], thus overriding any
 * language negotiation rule that was previously applied.
 *
 * @see template_preprocess()
 * @see template_preprocess_node()
 * @see template_process()
 */
?>

<?php /* Add dropdowntoc.js to html.*/ ?>
<script type="text/javascript" src="http://jrermockup.jermainemcdonald.com/sites/all/themes/jrerbartik/dropdowntoc.js"></script>

<?php
/* Jermaine McDonald, July 15, 2013
 * Get the values for the selected volume and put them in variables for local use.
 */
   $node_wrapper = entity_metadata_wrapper('node', $node);
   $vol_title = $node_wrapper->title->value();
   $vol_field_year = $node_wrapper->field_year->value();
   $vol_field_number = $node_wrapper->field_number->value();
   
/* Get the issue nodes associated with the given volume.
 * Added the function below to jrerbartik's template.php
 */
   $result = get_issueids_given_volumenumber($vol_field_number);
   // Create an array of nodes with information about the issue nodes associated with the volume.
   $issue_wrapper_array = array();
   
   // Process the issue ids by putting their node information into entity metadata wrappers
   foreach ($result as $record) {
      $issue_node = node_load($record->nid);
      $issue_wrapper_array[] = entity_metadata_wrapper('node', $issue_node);
   }
   
   // Start array for TOC list items
   $toc_issue_item_array = array();
   // Process entity wrappers. This will place TOC line for each issue associated with given Volume
   foreach ($issue_wrapper_array as $issue_node_wrapper) {
      $this_node_id = $issue_node_wrapper->nid->value();
      // Strip out the <p></p> tag embedded because we used filtered or full html to input formatted title
      $this_formatted_title = str_replace(array("<p>","</p>"), array("",""),
      					  $issue_node_wrapper->field_formatted_title->value->value());
      $this_author = $issue_node_wrapper->field_issue_author->value();
      $this_citation_author = $issue_node_wrapper->field_citation_author->value();
      $this_issue_number = $issue_node_wrapper->field_issue_number->value();
      $this_issue_date = $issue_node_wrapper->field_issue_date->value();
      $this_special_issue = $issue_node_wrapper->field_special_issue->value();
      // Drupal 7 Core has a bug. It throws an exception if you try to get a value from an entity
      // that does not have any infor in it. We could save the nodes in an array, use a counter to
      // track progress, and test the node represented by the file entity to see if it returns empty.
      // That, however is potentially a lot of overhead and we will be doing more work than necessary
      // when Drupal fixes this major (and annoying bug). So, we will simply catch the exception and
      // move on. Catch exceptions for field types: long text and file, but only if they can be blank.
      $this_abstract = ""; $this_issue_pdf_link = "";
      try { $this_abstract = $issue_node_wrapper->field_abstract->value->value(); } 
      catch (Exception $e) { $this_abstract = "No Abstract Provided."; }
      foreach ($issue_node_wrapper->field_issue_pdf->getIterator() as $delta =>$field_issue_pdf_wrapper) {
         try {
            $this_issue_pdf_url = $field_issue_pdf_wrapper->url->value();
         } catch (Exception $e) { $this_issue_pdf_url = ''; }
      }
      // Check if special issue, stand alone issue, or sub issue
      if ($this_special_issue) { // Special Issue
         $toc_issue_line = "<li>\n"
         		 . "<a id='menu-$this_issue_number' onclick='dropdown(this); return false;'>"
         		 . "<span class='specialissuename'>JRER Special Issue – "
         		 . "Volume $vol_field_number, Issue $this_issue_number</span><br />"
         		 . "<span class='issuename'>$this_formatted_title</span><br />"
         		 . "Edited by $this_author<br /></a>\n\n";
      } else if ( is_int($this_issue_number) ) { // Stand alone issue
         $toc_issue_line = "<li>\n"
         		 . "<a href='$this_issue_pdf_url' id='menu-$this_node_id'>"
         		 . "<span class='issuename'>$this_formatted_title</span><br />Written by $this_author<br />\n"
         		 . "Volume $vol_field_number, Issue $this_issue_number ($this_issue_date)\n"
         		 . "<img src='/modules/file/icons/application-pdf.png' /></a>\,</li>\n";
      } else { // Sub issue
         $toc_issue_line = "<li id='menu-$this_node_id'>\n"
         		 . "<a href='$this_issue_pdf_url' id='menu-$this_node_id'>"
         		 . "<span class='issuename'>$this_formatted_title</span>"
         		 . "<br />Written by $this_author<br />\n"
         		 . "Volume $vol_field_number, Issue $this_issue_number ($this_issue_date)\n"
         		 . "<img src='/modules/file/icons/application-pdf.png' /></a>\n</li>\n";
      }
      $this_key = $this_issue_number * 100; // Multiply by 100 for easy ordering (1.1=>110, 1.2=>120, 1.11=>111)
      $toc_issue_item_array[$this_key] = $toc_issue_line;
   }
   print "<ul id='menu-toc' class='dropdowntoc'>\n";
   // Sort the toc issue lines by issue order (i.e. the key value)
   ksort($toc_issue_item_array);
   $nest_toc = 0;
   foreach ($toc_issue_item_array as $key => $line){
      	if ( is_int($key/100) && !$nest_toc) {
      	   // Print line on main menu level.
      	   print $line;
      	} else if ( is_int($key/100) && $nest_toc ) {
      	   // Close nested menu (sub issue menu), set $nest_toc = 0, and print line
      	   print "</ul>\n</li>\n" . $line;
      	   $nest_toc = 0;
      	} else if ( !is_int($key/100) && $nest_toc) {
      	   // This is a sub issue and we have already started printing other sub issues. Carry on.
      	   print $line;
      	} else {
      	   // This is a sub issue and the previous issue was a main issue or the special issue header.
      	   // Open the nested menu, set $nest_toc = parent issue number, and print line
      	   $nest_toc = (int) ($key / 100);
      	   print "<ul id='menu-" . $nest_toc . "-submenu'>\n" . $line;
      	}
   }
   // Check if we need to close the nested menu.
   if ($nest_toc == 1) { print "</ul>\n</ul>"; }
   else { print "</ul>\n"; }
   
?>
<div id="node-<?php print $node->nid; ?>" class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>
  
  <?php print render($title_prefix); ?>
  <?php //We are going to replace all of this to customize our volume node display. - Jermaine McDonald
  if (!$page): ?>
    <h2<?php print $title_attributes; ?>>
      <a href="<?php print $node_url; ?>"><?php print $title; ?></a>
    </h2>
  <?php endif; ?>
  <?php print render($title_suffix); ?>

  <?php if ($display_submitted): ?>
    <div class="meta submitted">
      <?php print $user_picture; ?>
      <?php print $submitted; ?>
    </div>
  <?php endif; ?>

  <div class="content clearfix"<?php print $content_attributes; ?>>
    <?php
      // We hide the comments and links now so that we can render them later.
      //hide($content['comments']);
      //hide($content['links']);
      //print render($content);
    ?>
  </div>

  <?php
    // Remove the "Add new comment" link on the teaser page or if the comment
    // form is being displayed on the same page.
    if ($teaser || !empty($content['comments']['comment_form'])) {
      unset($content['links']['comment']['#links']['comment-add']);
    }
    // Only display the wrapper div if there are links.
    $links = render($content['links']);
    if ($links):
  ?>
    <div class="link-wrapper">
      <?php print $links; ?>
    </div>
  <?php endif; ?>

  <?php print render($content['comments']); ?>

</div>