/**
 * Teacher Dashboard JavaScript
 */

(function($) {
    'use strict';
    
    // Main dashboard object
    window.teacherDashboard = {
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.bindEvents();
            this.loadDashboard();
        },
        
        /**
         * Load students for a specific teacher
         */
        loadTeacherStudents: function(teacherId, teacherName) {
            const studentsSection = jQuery('#filtered-students');
            const studentsContent = jQuery('#teacher-students-content');
            const teacherNameSpan = jQuery('#selected-teacher-name');
            
            // Update header
            teacherNameSpan.text('- ' + teacherName);
            
            // Show loading
            studentsContent.html('<p>Loading students...</p>');
            
            // Show the filtered students section
            studentsSection.show();
            
            jQuery.ajax({
                url: teacherDashboardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_teacher_students',
                    nonce: teacherDashboardAjax.nonce,
                    teacher_id: teacherId
                },
                success: function(response) {
                    if (response.success) {
                        teacherDashboard.displayStudents(response.data);
                    } else {
                        studentsContent.html('<p>Error loading students: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    studentsContent.html('<p>Error connecting to server</p>');
                }
            });
        },
        
        /**
         * Display students in the filtered section
         */
        displayStudents: function(htmlContent) {
            const studentsContent = jQuery('#teacher-students-content');
            
            if (!htmlContent) {
                studentsContent.html('<p>No students found for this teacher.</p>');
                return;
            }
            
            // Insert the HTML content directly
            studentsContent.html(htmlContent);
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Refresh button
            jQuery(document).on('click', '.refresh-btn', function(e) {
                e.preventDefault();
                teacherDashboard.loadDashboard();
            });
            
            // View Students button
            jQuery(document).on('click', '.view-students-btn', function(e) {
                e.preventDefault();
                const teacherId = jQuery(this).data('teacher-id');
                const teacherName = jQuery(this).closest('tr').find('td:first strong').text();
                
                // Highlight selected teacher
                jQuery('.teacher-row').removeClass('selected');
                jQuery(this).closest('tr').addClass('selected');
                
                teacherDashboard.loadTeacherStudents(teacherId, teacherName);
            });
            
            // Auto-refresh every 5 minutes
            setInterval(this.loadDashboard.bind(this), 300000);
        },
        
        /**
         * Load dashboard data
         */
        loadDashboard: function() {
            var $container = $('.teacher-dashboard-container');
            if ($container.length === 0) return;
            
            this.showLoading();
            
            $.ajax({
                url: teacherDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'teacher_dashboard_data',
                    nonce: teacherDashboard.nonce
                },
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: this.hideLoading.bind(this)
            });
        },
        
        /**
         * Refresh dashboard
         */
        refresh: function() {
            // Show loading indicator
            jQuery('.dashboard-container').addClass('loading');
            
            jQuery.ajax({
                url: teacherDashboard.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'get_dashboard_data',
                    nonce: teacherDashboard.nonce,
                    role: teacherDashboard.userRole
                },
                success: function(response) {
                    if (response.success) {
                        teacherDashboard.updateDashboard(response.data);
                        teacherDashboard.showMessage('Dashboard data refreshed successfully', 'success');
                    } else {
                        teacherDashboard.showMessage('Failed to refresh data: ' + response.data, 'error');
                    }
                },
                error: function() {
                    teacherDashboard.showMessage('Error connecting to server', 'error');
                },
                complete: function() {
                    // Hide loading indicator
                    jQuery('.dashboard-container').removeClass('loading');
                }
            });
        },
        
        /**
         * Handle successful AJAX response
         */
        handleSuccess: function(response) {
            if (response.success && response.data) {
                this.updateDashboard(response.data);
                this.showMessage('Dashboard updated successfully', 'success');
            } else {
                this.showMessage('Failed to load dashboard data', 'error');
            }
        },
        
        /**
         * Handle AJAX error
         */
        handleError: function(xhr, status, error) {
            console.error('Dashboard AJAX Error:', error);
            this.showMessage('Error loading dashboard: ' + error, 'error');
        },
        
        /**
         * Update dashboard with new data
         */
        updateDashboard: function(data) {
            // Update each section
            if (data.quiz_stats) {
                this.updateQuizStats(data.quiz_stats);
            }
            
            if (data.groups) {
                this.updateGroups(data.groups);
            }
            
            if (data.students) {
                this.updateStudents(data.students);
            }
            
            // Update role-specific sections
            if (data.user_role === 'admin' && data.teachers) {
                this.updateTeachers(data.teachers);
            }
        },
        
        /**
         * Update teachers section
         */
        updateTeachers: function(teachers) {
            const container = jQuery('.dashboard-section.teachers');
            
            if (!container.length) {
                return;
            }
            
            if (!teachers || teachers.length === 0) {
                container.html('<h3>' + this.i18n.teachers + '</h3><p>' + this.i18n.noTeachersAvailable + '</p>');
                return;
            }
            
            // Process teachers data
            const processedTeachers = {};
            teachers.forEach(function(teacher) {
                const teacherId = teacher.teacher_id;
                
                if (!processedTeachers[teacherId]) {
                    processedTeachers[teacherId] = {
                        teacher_id: teacherId,
                        teacher_name: teacher.teacher_name,
                        user_email: teacher.user_email,
                        groups: [],
                        student_count: 0
                    };
                }
                
                // Add group to this teacher
                if (teacher.group_id) {
                    processedTeachers[teacherId].groups.push({
                        group_id: teacher.group_id,
                        group_name: teacher.group_name
                    });
                }
                
                // Add student count
                if (teacher.student_count) {
                    processedTeachers[teacherId].student_count += parseInt(teacher.student_count);
                }
            });
            
            // Convert to array
            const teachersList = Object.values(processedTeachers);
            
            // Create HTML
            let html = '<h3>' + this.i18n.teachers + '</h3>';
            
            html += '<div class="teachers-table"><table class="wp-list-table widefat fixed striped"><thead><tr>';
            html += '<th>' + this.i18n.teacherName + '</th>';
            html += '<th>' + this.i18n.email + '</th>';
            html += '<th>' + this.i18n.groups + '</th>';
            html += '<th>' + this.i18n.students + '</th>';
            html += '<th>' + this.i18n.actions + '</th>';
            html += '</tr></thead><tbody>';
            
            teachersList.forEach(function(teacher) {
                html += '<tr>';
                html += '<td><strong>' + teacher.teacher_name + '</strong></td>';
                html += '<td>' + teacher.user_email + '</td>';
                html += '<td>' + teacher.groups.length + ' ' + teacherDashboard.i18n.groupsLabel + '</td>';
                html += '<td>' + teacher.student_count + '</td>';
                html += '<td><a href="' + teacherDashboard.adminUrl + 'user-edit.php?user_id=' + teacher.teacher_id + '" class="button button-small">' + teacherDashboard.i18n.edit + '</a></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            container.html(html);
        },
        
        /**
         * Update quiz statistics
         */
        updateQuizStats: function(quizStats) {
            var $container = $('.quiz-stats-grid');
            if ($container.length === 0) return;
            
            $container.empty();
            
            if (quizStats.length === 0) {
                $container.html('<p>No quiz data available.</p>');
                return;
            }
            
            quizStats.forEach(function(quiz) {
                var $card = $('<div class="quiz-stat-card">');
                
                $card.html([
                    '<h4>' + this.escapeHtml(quiz.quiz_name) + '</h4>',
                    quiz.course_name ? '<p class="course-name">' + this.escapeHtml(quiz.course_name) + '</p>' : '',
                    '<div class="stats">',
                        '<div class="stat">',
                            '<span class="label">Attempts:</span>',
                            '<span class="value">' + quiz.total_attempts + '</span>',
                        '</div>',
                        '<div class="stat">',
                            '<span class="label">Avg Score:</span>',
                            '<span class="value">' + parseFloat(quiz.avg_score).toFixed(1) + '%</span>',
                        '</div>',
                        '<div class="stat">',
                            '<span class="label">Range:</span>',
                            '<span class="value">' + parseFloat(quiz.min_score).toFixed(1) + '% - ' + parseFloat(quiz.max_score).toFixed(1) + '%</span>',
                        '</div>',
                    '</div>'
                ].join(''));
                
                $container.append($card);
            }.bind(this));
        },
        
        /**
         * Update groups table
         */
        updateGroups: function(groups) {
            var $tbody = $('.groups-table tbody');
            if ($tbody.length === 0) return;
            
            $tbody.empty();
            
            if (groups.length === 0) {
                $tbody.html('<tr><td colspan="100%">No groups available.</td></tr>');
                return;
            }
            
            groups.forEach(function(group) {
                var $row = $('<tr>');
                
                $row.html([
                    '<td>' + this.escapeHtml(group.group_name) + '</td>',
                    group.teacher_name ? '<td>' + this.escapeHtml(group.teacher_name) + '</td>' : '',
                    '<td>' + (group.student_count || 0) + '</td>',
                    '<td><span class="status status-' + group.post_status + '">' + this.capitalize(group.post_status) + '</span></td>'
                ].join(''));
                
                $tbody.append($row);
            }.bind(this));
        },
        
        /**
         * Update students table
         */
        updateStudents: function(students) {
            var $tbody = $('.students-table tbody');
            if ($tbody.length === 0) return;
            
            $tbody.empty();
            
            if (students.length === 0) {
                $tbody.html('<tr><td colspan="4">No students available.</td></tr>');
                return;
            }
            
            students.forEach(function(student) {
                var $row = $('<tr>');
                var scoreHtml = student.avg_quiz_score ? 
                    '<span class="score">' + parseFloat(student.avg_quiz_score).toFixed(1) + '%</span>' :
                    '<span class="no-score">N/A</span>';
                
                $row.html([
                    '<td>' + this.escapeHtml(student.student_name) + '</td>',
                    '<td>' + this.escapeHtml(student.user_email) + '</td>',
                    '<td>' + this.escapeHtml(student.group_name || 'N/A') + '</td>',
                    '<td>' + scoreHtml + '</td>'
                ].join(''));
                
                $tbody.append($row);
            }.bind(this));
        },
        
        /**
         * Update teachers table (admin only)
         */
        updateTeachers: function(teachers) {
            var $tbody = $('.teachers-table tbody');
            if ($tbody.length === 0) return;
            
            $tbody.empty();
            
            if (teachers.length === 0) {
                $tbody.html('<tr><td colspan="4">No teachers available.</td></tr>');
                return;
            }
            
            teachers.forEach(function(teacher) {
                var $row = $('<tr>');
                
                $row.html([
                    '<td>' + this.escapeHtml(teacher.teacher_name) + '</td>',
                    '<td>' + this.escapeHtml(teacher.user_email) + '</td>',
                    '<td>' + this.escapeHtml(teacher.group_name || 'N/A') + '</td>',
                    '<td>' + (teacher.student_count || 0) + '</td>'
                ].join(''));
                
                $tbody.append($row);
            }.bind(this));
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            var $container = $('.teacher-dashboard-container');
            $container.addClass('loading');
            
            // Add loading overlay if it doesn't exist
            if ($container.find('.dashboard-loading').length === 0) {
                $container.append('<div class="dashboard-loading">Loading dashboard data...</div>');
            }
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            var $container = $('.teacher-dashboard-container');
            $container.removeClass('loading');
            $container.find('.dashboard-loading').remove();
        },
        
        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            var $container = $('.teacher-dashboard-container');
            var $message = $('<div class="dashboard-message dashboard-message-' + type + '">' + message + '</div>');
            
            $container.prepend($message);
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 3000);
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        },
        
        /**
         * Capitalize first letter
         */
        capitalize: function(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.teacher-dashboard-container').length > 0) {
            teacherDashboard.init();
        }
    });
    
})(jQuery);
