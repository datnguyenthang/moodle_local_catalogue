<?php

function get_category_paths() {
    global $DB;

    // Retrieve all categories
    $categories = $DB->get_records('local_catalogue_categories', null, 'name ASC');
    $category_paths = [];  // To hold the category paths
    $categories_by_id = []; // To hold categories by ID for easy access

    // Organize categories by ID for quick access
    foreach ($categories as $category) {
        $categories_by_id[$category->id] = $category;
    }

    // Function to build category path recursively
    $build_path = function($category_id) use ($categories_by_id, &$build_path) {
        $category = isset($categories_by_id[$category_id]) ? $categories_by_id[$category_id] : null;

        // Base case: If category doesn't exist, return empty string
        if (!$category) {
            return '';
        }

        // If there's no parent, return the category name
        if (!$category->parent) {
            return $category->name;
        }

        // Recursive case: get the parent's path
        $parent_path = $build_path($category->parent);

        // Return the complete path
        return $parent_path ? $parent_path . ' > ' . $category->name : $category->name;
    };

    // Loop through each category to build its path
    foreach ($categories as $category) {
        $category_paths[] = (object) [
            'id' => $category->id,
            'path' => $build_path($category->id)
        ];
    }

    return $category_paths;
}

function build_tree($elements, $parent = 0) {
    $branch = [];
    
    foreach ($elements as $element) {
        // Assuming $element is an object, not an array.
        if (is_object($element) && $element->parent == $parent) {
            // Recursively build the tree
            $children = build_tree($elements, $element->id);
            if ($children) {
                $element->children = $children;  // Attach children to the current element
            }
            $branch[] = $element;  // Add the current element to the branch
        }
    }

    return $branch;
}

function render_dynamic_level($item) {
    // Main container for the hierarchy item
    $html = '<div class="hierarchy-item" style="border: none;">';
    
    // Header and toggler (with chevron icons, checkbox, and item name in the same row)
    $html .= '<div class="d-flex align-items-center courseindex-item courseindex-section-title">';

    // Only render the toggle button with chevron if the item has children
    if (isset($item->children) && !empty($item->children)) {
        // Toggle button with icons for collapse/expand
        $html .= '<a data-toggle="collapse" href="#collapse' . $item->id . '" role="button" aria-expanded="false" aria-controls="collapse' . $item->id . '" class="courseindex-chevron icons-collapse-expand collapsed">';

        // Collapsed chevron icon (right chevron)
        $html .= '<span class="collapsed-icon icon-no-margin p-1 mr-2" title="Expand">';
        $html .= '<span class="dir-rtl-hide" tabindex="-1">';
        $html .= '<i class="icon fa fa-chevron-right fa-fw" aria-hidden="true"></i>';
        $html .= '</span>';
        $html .= '</span>';

        // Expanded chevron icon (down chevron)
        $html .= '<span class="expanded-icon icon-no-margin p-1 mr-2" title="Collapse">';
        $html .= '<span class="dir-rtl-hide" tabindex="-1">';
        $html .= '<i class="icon fa fa-chevron-down fa-fw" aria-hidden="true"></i>';
        $html .= '</span>';
        $html .= '</span>';

        $html .= '</a>'; // End the link wrapping the toggle
    } else {
        // If no children, add a placeholder to align the checkbox and name properly
        $html .= '<span class="me-4 ml-3"></span>';
    }
    
    // Item name with small font
    $html .= '<span class="small">' . $item->name . '</span>';
    $html .= html_writer::link('#', '<i class="fa fa-edit"></i>', array('class' => 'btn btn-primary btn-sm', 'title' => get_string('edit'), 'data-category' => $item->id), 'data-acton="edit"');
    $html .= html_writer::link('#', '<i class="fa fa-trash"></i>', array('class' => 'btn btn-danger btn-sm', 'title' => get_string('delete'), 'data-category' => $item->id), 'data-acton="delete"');

    
    $html .= '</div>'; // End courseindex-section-title
    
    // Collapsible section for child elements (if any)
    if (isset($item->children) && !empty($item->children)) {
        $html .= '<div id="collapse' . $item->id . '" class="hierarchy-collapse collapse">';
        $html .= '<div class="hierarchy-body ms-3">'; // Indent child elements

        // Recursively render children if present
        foreach ($item->children as $child) {
            $html .= render_dynamic_level($child);
        }

        $html .= '</div>'; // End hierarchy-body
        $html .= '</div>'; // End collapsible section
    }

    $html .= '</div>'; // End hierarchy-item

    return $html;
}

function render_dynamic_level_with_link($item, $selected_category_id) {
    global $CFG;

    // Check if the current item is selected
    $is_selected = ($item->id == $selected_category_id);

    // Check if any child categories are selected
    $has_active_child = has_selected_child($item->id, $selected_category_id);

    // Determine if this item should expand
    $should_expand = $is_selected || $has_active_child;

    // Main container for the hierarchy item
    $html = '<div class="hierarchy-item" style="border: none;">';

    // Header and toggler (with chevron icons, checkbox, and item name in the same row)
    $html .= '<div class="d-flex align-items-center courseindex-item courseindex-section-title">';

    // Only render the toggle button with chevron if the item has children
    if (isset($item->children) && !empty($item->children)) {
        // Toggle button with icons for collapse/expand
        $html .= '<a data-toggle="collapse" href="#collapse' . $item->id . '" role="button" aria-expanded="' . ($should_expand ? 'true' : 'false') . '" aria-controls="collapse' . $item->id . '" class="courseindex-chevron icons-collapse-expand' . ($should_expand ? '' : ' collapsed') . '">';

        // Collapsed chevron icon (right chevron)
        $html .= '<span class="collapsed-icon icon-no-margin p-1 mr-2" title="Expand">';
        $html .= '<span class="dir-rtl-hide" tabindex="-1">';
        $html .= '<i class="icon fa fa-chevron-right fa-fw" aria-hidden="true"></i>';
        $html .= '</span>';
        $html .= '</span>';

        // Expanded chevron icon (down chevron)
        $html .= '<span class="expanded-icon icon-no-margin p-1 mr-2" title="Collapse">';
        $html .= '<span class="dir-rtl-hide" tabindex="-1">';
        $html .= '<i class="icon fa fa-chevron-down fa-fw" aria-hidden="true"></i>';
        $html .= '</span>';
        $html .= '</span>';

        $html .= '</a>'; // End the link wrapping the toggle
    } else {
        // If no children, add a placeholder to align the checkbox and name properly
        $html .= '<span class="me-4 ml-3"></span>';
    }

    $active_class = ($item->id == $selected_category_id) ? ' bg-secondary' : '';
    $html .= '<span class="small' . $active_class . '">';
    $html .= html_writer::link('#', $item->name, ['class' => 'category-link', 'data-category' => $item->id, 'data-action' => 'view']);
    $html .= '</span>';

    // Edit, view and delete links
    if ($item->visible) $visible = '<i class="fa fa-eye"></i>';
    else $visible = '<i class="fa fa-eye-slash"></i>';

    $html .= html_writer::link('#', '<i class="fa fa-edit"></i>', ['class' => 'btn-sm create_category_button pl-5 pe-0', 'data-category' => $item->id, 'title' => get_string('edit'), 'data-acton' => 'edit']);
    $html .= html_writer::link('#', $visible, ['class' => 'btn-sm change_visible_button pe-0', 'data-category' => $item->id, 'title' => get_string('view'), 'data-acton' => 'visible']);
    $html .= html_writer::link('#', '<i class="fa fa-trash"></i>', ['class' => 'btn-sm delete_category_button pe-0', 'data-category' => $item->id, 'title' => get_string('delete'), 'data-acton' => 'delete']);

    $html .= '</div>'; // End courseindex-section-title

    // Collapsible section for child elements (if any)
    if (isset($item->children) && !empty($item->children)) {
        $html .= '<div id="collapse' . $item->id . '" class="hierarchy-collapse collapse' . ($should_expand ? ' show' : '') . '">';
        $html .= '<div class="hierarchy-body ms-3">'; // Indent child elements

        // Recursively render children if present
        foreach ($item->children as $child) {
            $html .= render_dynamic_level_with_link($child, $selected_category_id);
        }

        $html .= '</div>'; // End hierarchy-body
        $html .= '</div>'; // End collapsible section
    }

    $html .= '</div>'; // End hierarchy-item

    return $html;
}

function has_selected_child($category_id, $selected_category_id) {
    global $DB;

    // Query to get child categories of the current category
    $sql = "SELECT id FROM {local_catalogue_categories} WHERE parent = :category_id";
    $params = ['category_id' => $category_id];

    // Fetch child categories
    $children = $DB->get_records_sql($sql, $params);

    if (empty($children)) {
        return false;
    }

    // Check if any of the child categories are selected
    foreach ($children as $child) {
        if ($child->id == $selected_category_id || has_selected_child($child->id, $selected_category_id)) {
            return true;
        }
    }

    return false;
}

function display_category_catalogue($selected_category_id = null) {
    global $DB, $CFG;
    $categories = $DB->get_records('local_catalogue_categories', null, 'name ASC');

    // Action button for creating a new category
    $html .= html_writer::start_tag('div', ['class' => 'listing-actions category-listing-actions text-center']);
    $html .= html_writer::start_tag('button', [
        'type' => 'button',
        'data-category' => '',
        'class' => 'btn btn-primary btn-sm create_category_button',
    ]);
    $html .= get_string('createnewcategory', 'local_catalogue');
    $html .= html_writer::end_tag('button');
    $html .= html_writer::end_tag('div');

    if ($categories) {
        $tree_category = build_tree($categories);

        $html .= '<div class="mb-3 mt-3 row fitem" id="hierarchy-expand"><div class="col-md-9 checkbox">';
        foreach ($tree_category as $item) {
            $html .= render_dynamic_level_with_link($item, $selected_category_id);
        }
        $html .= '</div></div>';
    } else {
        $html .= html_writer::tag('p', get_string('nocategories', 'local_catalogue'), ['class' => 'text-muted p-4']);
    }

    return $html;
}

function display_course_catalogue($category_id) {
    global $DB, $OUTPUT;

    $category = $DB->get_record('local_catalogue_categories', ['id' => $category_id ?? null]);
    $courses = $DB->get_records('local_catalogue_courses', ['category_id' => $category_id ?? null], 'name ASC');

    // Action button for creating a new course
    $html .= html_writer::start_tag('div', ['class' => 'listing-actions category-listing-actions text-center mb-3']);
    $html .= html_writer::start_tag('button', [
        'type' => 'button',
        'class' => 'btn btn-primary btn-sm create_course_button',
        'data-category' => $category_id,
        'data-course' => 0,
    ]);
    $html .= get_string('createnewcourse', 'local_catalogue');
    $html .= html_writer::end_tag('button');
    $html .= html_writer::end_tag('div');

    if ($courses) {
        $html .= html_writer::start_tag('ul', ['class' => 'list-group p-0']);
        $index = 1;
        foreach ($courses as $course) {
            if ($course->visible) $visible = '<i class="fa fa-eye"></i>';
            else $visible = '<i class="fa fa-eye-slash"></i>';

            $html .= html_writer::start_tag('li', ['class' => 'list-group-item d-flex justify-content-between align-items-center']);
            $html .= html_writer::tag('span', $index++, ['class' => 'badge bg-gray rounded-pill']);
            $html .= html_writer::tag('span', $course->name, ['class' => 'text-truncate me-3']);

            $html .= html_writer::start_tag('div', ['class' => 'btn-group']);
            $html .= html_writer::link('#', html_writer::tag('i', '', ['class' => 'fa fa-pencil fa-sm pe-0', 'aria-hidden' => 'true']), ['class' => 'btn-sm create_course_button', 'title' => get_string('edit'), 'data-course' => $course->id, 'data-category' => $category_id , 'data-action' => 'edit']);
            $html .= html_writer::link('#', $visible, ['class' => 'btn-sm change_visible_button pe-0 pl-0', 'data-category' => $category_id, 'data-course' => $course->id, 'title' => get_string('view'), 'data-acton' => 'visible']);
            $html .= html_writer::link('#', html_writer::tag('i', '', ['class' => 'fa fa-trash ', 'aria-hidden' => 'true']), ['class' => 'btn-sm delete_course_button fa-sm pe-0', 'title' => get_string('delete'), 'data-course' => $course->id,  'data-category' => $category_id, 'data-action' => 'delete']);
            $html .= html_writer::end_tag('div');

            $html .= html_writer::end_tag('li');
        }

        $html .= html_writer::end_tag('ul');
    } else {
        $html .= html_writer::tag('p', get_string('nocourses', 'local_catalogue'), ['class' => 'text-muted p-4']);
    }

    return $html;
}