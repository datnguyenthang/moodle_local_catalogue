define(['jquery', 'core/ajax', 'core/templates', 'core_form/modalform', 'core/str', 'core/toast'], function($, ajax, templates, ModalForm, {getString}, {notifyUser} ) {
    return {
        init: function() {
            const loadCategory = (category = 0) => {
                $('#catalogue-loading-icon').show();

                ajax.call([{
                    methodname: 'local_catalogue_get_admin_category',
                    args: {
                        category: category,
                    }
                }])[0].done(function(response) {
                    // Render the category.
                    templates.render('local_catalogue/category_list', { response: response })
                        .done(function(html) {
                            $('#category-container').html(html).show();
                            $('#catalogue-loading-icon').hide();
                        });
                }).fail(function(ex) {
                    console.error(ex);
                    $('#catalogue-loading-icon').hide(); // Hide loading icon on error
                    $('#category-container').show();
                });
            };
            loadCategory();

            const loadCourse = (category = 0) => {
                $('#course-loading-icon').show();

                ajax.call([{
                    methodname: 'local_catalogue_get_admin_list_course',
                    args: {
                        category: category,
                    }
                }])[0].done(function(response) {
                    // Render the course.
                    templates.render('local_catalogue/course_list', { response: response })
                        .done(function(html) {
                            $('#course-container').html(html).show();
                            $('#course-loading-icon').hide();
                        });
                }).fail(function(ex) {
                    console.error(ex);
                    $('#course-loading-icon').hide(); // Hide loading icon on error
                    $('#course-container').show();
                });
            }
            loadCourse();

            $('#category-container').on('click', '.category-link', function() {
                const category = $(this).data('category');
                loadCourse(category);
            });

            $('#category-container').on('click', '.create_category_button', function(e) {
                    e.preventDefault();
                    const element = e.target.closest('.create_category_button');
                    const category = element.getAttribute('data-category');
                    const modalForm = new ModalForm({
                        formClass: "local_catalogue\\form\\category_form",
                        args: {id: element.getAttribute('data-category')},
                        modalConfig: {title: getString('createnewcategory', 'local_catalogue', element.getAttribute('data-name'))},
                        returnFocus: element,
                    });
    
                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
                        window.console.log(e.detail);
                        const data = e.detail;
                        const id = Number(data.id) && !isNaN(Number(data.id)) ? Number(data.id) : 0;
                        loadCategory(id);
                    
                        if (data.message && data.message.length > 0) {
                          showNotification(data.message, data.success == 1 ? 'success' : 'danger');
                        }
                      });
                    modalForm.show();
            });

            $('#course-container').on('click', '.create_course_button', function(e) {
                e.preventDefault();
                const element = e.target.closest('.create_course_button');
                const course = element.getAttribute('data-course');
                const category = element.getAttribute('data-category');
                const modalForm = new ModalForm({
                    formClass: "local_catalogue\\form\\course_form",
                    args: {
                        id: course,
                        category: category
                    },
                    modalConfig: {title: getString('createnewcourse', 'local_catalogue', element.getAttribute('data-name'))},
                    returnFocus: element,
                });

                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
                    window.console.log(e.detail);
                    const data = e.detail;
                    loadCourse(category);
                
                    if (data.message && data.message.length > 0) {
                      showNotification(data.message, data.success == 1 ? 'success' : 'danger');
                    }
                  });
                modalForm.show();
            });
            
            $('#course-container').on('click', '.delete_course_button', function(e) {
                e.preventDefault();
                const element = e.target.closest('.delete_course_button');
                const category = element.getAttribute('data-category');
                const course = element.getAttribute('data-course');
                ajax.call([{
                    methodname: 'local_catalogue_admin_delete_course',
                    args: {
                        course: course,
                    }
                }])[0].done(function(response) {
                    loadCourse(category);
                }).fail(function(ex) {
                    console.error(ex);
                });
            });

            $('#category-container').on('click', '.delete_category_button', function(e) {
                e.preventDefault();
                const element = e.target.closest('.delete_category_button');
                const category = element.getAttribute('data-category');
                ajax.call([{
                    methodname: 'local_catalogue_admin_delete_category',
                    args: {
                        category: category,
                    }
                }])[0].done(function(response) {
                    loadCategory();
                }).fail(function(ex) {
                    console.error(ex);
                });
            });

        }
    };
});