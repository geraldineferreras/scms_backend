<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class TeacherController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Implement the index method
    }

    public function create()
    {
        // Implement the create method
    }

    public function update()
    {
        // Implement the update method
    }

    public function delete()
    {
        // Implement the delete method
    }

    // --- Teacher Classroom Management ---
    public function classrooms_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $classrooms = $this->Classroom_model->get_all();
        $result = [];
        foreach ($classrooms as $classroom) {
            // Fetch subject name
            $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
            $subject_name = $subject ? $subject['subject_name'] : '';
            // Fetch section name
            $section = $this->Section_model->get_by_id($classroom['section_id']);
            $section_name = $section ? $section['section_name'] : '';
            // Count students in section (users table, role=student)
            $student_count = $this->db->where('section_id', $classroom['section_id'])->where('role', 'student')->count_all_results('users');
            $result[] = [
                'class_code' => $classroom['class_code'],
                'subject_name' => $subject_name,
                'section_name' => $section_name,
                'semester' => $classroom['semester'],
                'school_year' => $classroom['school_year'],
                'student_count' => $student_count
            ];
        }
        return json_response(true, 'Classrooms retrieved successfully', $result);
    }

    public function classroom_get($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_id($id);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        return json_response(true, 'Classroom retrieved successfully', $classroom);
    }

    public function classrooms_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['subject_id', 'section_id', 'semester', 'school_year'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        $data['teacher_id'] = $user_data['user_id'];
        if (empty($data['title']) && !empty($data['custom_title'])) {
            $data['title'] = $data['custom_title'];
        }
        unset($data['custom_title']);
        $id = $this->Classroom_model->insert($data);
        if ($id) {
            // Fetch subject name
            $this->load->model('Subject_model');
            $subject = $this->Subject_model->get_by_id($data['subject_id']);
            $subject_name = $subject ? $subject['subject_name'] : '';
            // Fetch section name
            $this->load->model('Section_model');
            $section = $this->Section_model->get_by_id($data['section_id']);
            $section_name = $section ? $section['section_name'] : '';
            // Count students in section (users table, role=student)
            $student_count = $this->db->where('section_id', $data['section_id'])->where('role', 'student')->count_all_results('users');
            // Get class_code
            $classroom = $this->Classroom_model->get_by_id($id);
            $class_code = $classroom['class_code'];
            $response = [
                'class_code' => $class_code,
                'subject_name' => $subject_name,
                'section_name' => $section_name,
                'semester' => $data['semester'],
                'school_year' => $data['school_year'],
                'student_count' => $student_count
            ];
            return json_response(true, 'Classroom created successfully', $response, 201);
        } else {
            return json_response(false, 'Failed to create classroom', null, 500);
        }
    }

    public function classrooms_put($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->Classroom_model->update($id, $data);
        if ($success) {
            return json_response(true, 'Classroom updated successfully');
        } else {
            return json_response(false, 'Failed to update classroom', null, 500);
        }
    }

    public function classrooms_delete($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $success = $this->Classroom_model->delete($id);
        if ($success) {
            return json_response(true, 'Classroom deleted successfully');
        } else {
            return json_response(false, 'Failed to delete classroom', null, 500);
        }
    }

    public function classroom_by_code_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
        $subject_name = $subject ? $subject['subject_name'] : '';
        $section = $this->Section_model->get_by_id($classroom['section_id']);
        $section_name = $section ? $section['section_name'] : '';
        $student_count = $this->db->where('section_id', $classroom['section_id'])->where('role', 'student')->count_all_results('users');
        $response = [
            'class_code' => $classroom['class_code'],
            'subject_name' => $subject_name,
            'section_name' => $section_name,
            'semester' => $classroom['semester'],
            'school_year' => $classroom['school_year'],
            'student_count' => $student_count
        ];
        return json_response(true, 'Classroom retrieved successfully', $response);
    }

    public function classrooms_put_by_code($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $success = $this->Classroom_model->update($classroom['id'], $data);
        if ($success) {
            return json_response(true, 'Classroom updated successfully');
        } else {
            return json_response(false, 'Failed to update classroom', null, 500);
        }
    }

    public function classrooms_delete_by_code($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $success = $this->Classroom_model->delete($classroom['id']);
        if ($success) {
            return json_response(true, 'Classroom deleted successfully');
        } else {
            return json_response(false, 'Failed to delete classroom', null, 500);
        }
    }

    public function classroom_stream_post($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        // student_ids is optional: if provided, only those students can see the post
        $insert_data = [
            'class_code' => $class_code,
            'user_id' => $user_data['user_id'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'is_draft' => $data['is_draft'] ?? 0,
            'is_scheduled' => $data['is_scheduled'] ?? 0,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'allow_comments' => $data['allow_comments'] ?? 1,
            'attachment_type' => $data['attachment_type'] ?? null,
            'attachment_url' => $data['attachment_url'] ?? null
        ];
        if (!empty($data['student_ids'])) {
            $insert_data['student_ids'] = $data['student_ids'];
        }
        $id = $this->ClassroomStream_model->insert($insert_data);
        if ($id) {
            $post = $this->ClassroomStream_model->get_by_id($id);
            return json_response(true, 'Announcement posted successfully', $post, 201);
        } else {
            return json_response(false, 'Failed to post announcement', null, 500);
        }
    }

    public function classroom_stream_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_stream_for_classroom_ui($class_code);
        return json_response(true, 'Stream posts retrieved successfully', $posts);
    }

    // Like a stream post
    public function classroom_stream_like_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
        if (!in_array($user_data['user_id'], $likes)) {
            $likes[] = $user_data['user_id'];
            $this->db->where('id', $stream_id)->update('classroom_stream', [
                'liked_by_user_ids' => json_encode($likes)
            ]);
        }
        return json_response(true, 'Post liked successfully');
    }

    // Unlike a stream post
    public function classroom_stream_unlike_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
        $likes = array_diff($likes, [$user_data['user_id']]);
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'liked_by_user_ids' => json_encode(array_values($likes))
        ]);
        return json_response(true, 'Post unliked successfully');
    }

    // Pin a stream post
    public function classroom_stream_pin_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'is_pinned' => 1
        ]);
        return json_response(true, 'Post pinned successfully');
    }

    // Unpin a stream post
    public function classroom_stream_unpin_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'is_pinned' => 0
        ]);
        return json_response(true, 'Post unpinned successfully');
    }

    public function classroom_stream_scheduled_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_scheduled_for_classroom_ui($class_code);
        return json_response(true, 'Scheduled posts retrieved successfully', $posts);
    }

    public function classroom_stream_drafts_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_drafts_for_classroom_ui($class_code);
        return json_response(true, 'Draft posts retrieved successfully', $posts);
    }

    // Update a draft by ID (can also publish by setting is_draft=0)
    public function classroom_stream_draft_put($class_code, $draft_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $draft = $this->ClassroomStream_model->get_by_id($draft_id);
        if (!$draft || $draft['class_code'] !== $class_code || $draft['is_draft'] != 1) {
            return json_response(false, 'Draft not found', null, 404);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->ClassroomStream_model->update($draft_id, $data);
        if ($success) {
            $post = $this->ClassroomStream_model->get_by_id($draft_id);
            return json_response(true, 'Draft updated successfully', $post);
        } else {
            return json_response(false, 'Failed to update draft', null, 500);
        }
    }

    // Add a comment to a stream post
    public function classroom_stream_comment_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['comment'])) {
            return json_response(false, 'Comment is required', null, 400);
        }
        $comment_id = $this->ClassroomStream_model->add_comment($stream_id, $user_data['user_id'], $data['comment']);
        if ($comment_id) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment added successfully', $comments);
        } else {
            return json_response(false, 'Failed to add comment', null, 500);
        }
    }

    // Get all comments for a stream post
    public function classroom_stream_comments_get($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $comments = $this->ClassroomStream_model->get_comments($stream_id);
        return json_response(true, 'Comments retrieved successfully', $comments);
    }

    // Edit a comment
    public function classroom_stream_comment_put($class_code, $stream_id, $comment_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['comment'])) {
            return json_response(false, 'Comment is required', null, 400);
        }
        $success = $this->ClassroomStream_model->update_comment($comment_id, $user_data['user_id'], $data['comment']);
        if ($success) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment updated successfully', $comments);
        } else {
            return json_response(false, 'Failed to update comment (maybe not your comment)', null, 403);
        }
    }

    // Delete a comment
    public function classroom_stream_comment_delete($class_code, $stream_id, $comment_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $success = $this->ClassroomStream_model->delete_comment($comment_id, $user_data['user_id']);
        if ($success) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment deleted successfully', $comments);
        } else {
            return json_response(false, 'Failed to delete comment (maybe not your comment)', null, 403);
        }
    }
}
