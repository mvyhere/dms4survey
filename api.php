<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/config.php';

function respond($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_input()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid JSON payload.',
        ]);
    }

    return $data;
}

function normalize_text($value, $maxLength)
{
    $text = trim((string) $value);
    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
    }

    return $text;
}

function role_routes()
{
    return [
        'parent_caregiver' => 'parent',
        'teacher_educator' => 'teacher',
        'therapist_specialist' => 'teacher',
        'family_member' => 'general',
        'friend_acquaintance' => 'general',
        'student_researcher' => 'general',
        'general_interest' => 'general',
        'other' => 'general',
    ];
}

function get_route_from_role($roleKey)
{
    $routes = role_routes();
    return isset($routes[$roleKey]) ? $routes[$roleKey] : null;
}

function route_matches($routeConstraint, $routeKey)
{
    if (is_array($routeConstraint)) {
        return in_array($routeKey, $routeConstraint, true);
    }

    return $routeConstraint === $routeKey;
}

function survey_specs()
{
    $frequencyOptions = ['daily', 'few_times_week', 'occasionally', 'rarely', 'never'];
    $shiftOptions = ['easy_shift', 'slow_shift_needs_prompt', 'hard_to_leave_old', 'not_sure'];
    $attentionOptions = ['balanced_attention', 'slight_detail_bias', 'mostly_small_details', 'not_sure'];
    $effectivenessOptions = [
        'extremely_effective',
        'very_effective',
        'moderately_effective',
        'slightly_effective',
        'not_effective',
        'not_sure',
    ];
    $scanTimeOptions = ['instant', 'under_5_seconds', 'under_10_seconds', 'under_20_seconds', 'wait_for_quality'];
    $trackOptions = [
        'time_focused',
        'pages_completed',
        'vocabulary_mastered',
        'sensory_triggers_identified',
        'preferred_objects_topics',
        'repeated_prompts_needed',
    ];
    $necessityOptions = ['extremely_necessary', 'very_necessary', 'somewhat_necessary', 'slightly_necessary', 'not_necessary'];
    $appUsefulnessOptions = ['extremely_useful', 'very_useful', 'somewhat_useful', 'slightly_useful', 'not_useful'];
    $outcomeOptions = ['longer_attention', 'better_understanding', 'reduced_frustration', 'easier_shared_reading', 'better_language'];
    $trustOptions = [
        'custom_sensory_settings',
        'clear_progress_tracking',
        'fast_scan_processing',
        'works_with_existing_books',
        'calm_simple_interface',
        'affordable_practical_pricing',
        'professional_recommendations',
    ];
    $pricingOptions = [
        'less_than_100k',
        'vnd_100k_200k',
        'vnd_200k_500k',
        'more_than_500k',
        'only_if_free',
    ];

    return [
        'parent_material_frequency' => [
            'type' => 'single',
            'route' => 'parent',
            'required' => true,
            'options' => $frequencyOptions,
        ],
        'teacher_material_frequency' => [
            'type' => 'single',
            'route' => 'teacher',
            'required' => true,
            'options' => $frequencyOptions,
        ],
        'teacher_best_book_type' => [
            'type' => 'single',
            'route' => 'teacher',
            'required' => true,
            'options' => [
                'simple_picture_books',
                'repetitive_predictable_stories',
                'social_stories',
                'sensory_audio_books',
                'digital_interactive_books',
                'depends_on_child',
                'physical_picture_books',
                'not_sure',
            ],
        ],
        'common_transition_shift' => [
            'type' => 'single',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'options' => $shiftOptions,
        ],
        'common_attention_style' => [
            'type' => 'single',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'options' => $attentionOptions,
        ],
        'parent_interactive_books_effectiveness' => [
            'type' => 'single',
            'route' => 'parent',
            'required' => true,
            'options' => $effectivenessOptions,
        ],
        'parent_scan_time' => [
            'type' => 'single',
            'route' => 'parent',
            'required' => true,
            'options' => $scanTimeOptions,
        ],
        'parent_track_data' => [
            'type' => 'multi',
            'route' => 'parent',
            'required' => true,
            'max' => 2,
            'options' => $trackOptions,
        ],
        'common_necessity' => [
            'type' => 'single',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'options' => $necessityOptions,
        ],
        'general_app_usefulness' => [
            'type' => 'single',
            'route' => 'general',
            'required' => true,
            'options' => $appUsefulnessOptions,
        ],
        'common_outcome' => [
            'type' => 'single',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'options' => $outcomeOptions,
        ],
        'parent_visual_settings_importance' => [
            'type' => 'scale',
            'route' => 'parent',
            'required' => true,
            'min' => 1,
            'max' => 5,
        ],
        'common_trust_factors' => [
            'type' => 'multi',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'max' => 2,
            'options' => $trustOptions,
        ],
        'common_monthly_price' => [
            'type' => 'single',
            'route' => ['parent', 'teacher', 'general'],
            'required' => true,
            'options' => $pricingOptions,
        ],
        'parent_open_feedback' => [
            'type' => 'text',
            'route' => 'parent',
            'required' => false,
            'max_length' => 3000,
        ],
        'teacher_open_feedback' => [
            'type' => 'text',
            'route' => 'teacher',
            'required' => false,
            'max_length' => 3000,
        ],
        'general_open_feedback' => [
            'type' => 'text',
            'route' => 'general',
            'required' => false,
            'max_length' => 3000,
        ],
    ];
}

function validate_answer_value($questionKey, array $spec, $value)
{
    $type = $spec['type'];

    if ($type === 'single') {
        $answer = trim((string) $value);
        if ($answer === '') {
            return null;
        }

        if (!in_array($answer, $spec['options'], true)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid answer for ' . $questionKey . '.',
            ]);
        }

        return $answer;
    }

    if ($type === 'multi') {
        if (!is_array($value)) {
            return [];
        }

        $answers = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '' && !in_array($item, $answers, true)) {
                $answers[] = $item;
            }
        }

        if (count($answers) > (int) $spec['max']) {
            respond(422, [
                'success' => false,
                'message' => 'Too many selections for ' . $questionKey . '.',
            ]);
        }

        foreach ($answers as $answer) {
            if (!in_array($answer, $spec['options'], true)) {
                respond(422, [
                    'success' => false,
                    'message' => 'Invalid selection for ' . $questionKey . '.',
                ]);
            }
        }

        return $answers;
    }

    if ($type === 'scale') {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            respond(422, [
                'success' => false,
                'message' => 'Scale answer must be numeric for ' . $questionKey . '.',
            ]);
        }

        $number = (int) $value;
        if ($number < (int) $spec['min'] || $number > (int) $spec['max']) {
            respond(422, [
                'success' => false,
                'message' => 'Scale answer out of range for ' . $questionKey . '.',
            ]);
        }

        return $number;
    }

    if ($type === 'text') {
        $text = normalize_text($value, (int) $spec['max_length']);
        return $text === '' ? null : $text;
    }

    respond(500, [
        'success' => false,
        'message' => 'Unsupported question type.',
    ]);
}

function validate_submission($payload)
{
    $name = normalize_text(isset($payload['name']) ? $payload['name'] : '', 150);
    $gender = trim((string) (isset($payload['gender']) ? $payload['gender'] : ''));
    $genderOther = normalize_text(isset($payload['gender_other']) ? $payload['gender_other'] : '', 150);
    $ageRaw = isset($payload['age']) ? $payload['age'] : null;
    $role = trim((string) (isset($payload['role']) ? $payload['role'] : ''));
    $roleOther = normalize_text(isset($payload['role_other']) ? $payload['role_other'] : '', 150);
    $language = trim((string) (isset($payload['language']) ? $payload['language'] : 'en'));
    $answers = isset($payload['answers']) && is_array($payload['answers']) ? $payload['answers'] : [];

    if ($name === '') {
        respond(422, ['success' => false, 'message' => 'Name is required.']);
    }

    $validGenders = ['male', 'female', 'prefer_not_say', 'other'];
    if (!in_array($gender, $validGenders, true)) {
        respond(422, ['success' => false, 'message' => 'Please choose a valid gender option.']);
    }

    if ($gender === 'other' && $genderOther === '') {
        respond(422, ['success' => false, 'message' => 'Please specify the other gender option.']);
    }

    if (!is_numeric($ageRaw)) {
        respond(422, ['success' => false, 'message' => 'Age must be a number.']);
    }

    $age = (int) $ageRaw;
    if ($age < 1 || $age > 120) {
        respond(422, ['success' => false, 'message' => 'Age must be between 1 and 120.']);
    }

    $route = get_route_from_role($role);
    if ($route === null) {
        respond(422, ['success' => false, 'message' => 'Please choose a valid relationship option.']);
    }

    if ($role === 'other' && $roleOther === '') {
        respond(422, ['success' => false, 'message' => 'Please specify the other relationship option.']);
    }

    if (!in_array($language, ['en', 'vi'], true)) {
        $language = 'en';
    }

    $specs = survey_specs();
    $validatedAnswers = [];

    foreach ($specs as $questionKey => $spec) {
        if (!route_matches($spec['route'], $route)) {
            continue;
        }

        $rawValue = array_key_exists($questionKey, $answers) ? $answers[$questionKey] : null;
        $validatedValue = validate_answer_value($questionKey, $spec, $rawValue);

        $isEmpty = $validatedValue === null
            || (is_array($validatedValue) && count($validatedValue) === 0)
            || $validatedValue === '';

        if ($spec['required'] && $isEmpty) {
            respond(422, [
                'success' => false,
                'message' => 'Please answer all required questions before submitting.',
            ]);
        }

        if (!$isEmpty) {
            $validatedAnswers[$questionKey] = $validatedValue;
        }
    }

    return [
        'name' => $name,
        'gender' => $gender,
        'gender_other' => $genderOther,
        'age' => $age,
        'role' => $role,
        'role_other' => $roleOther,
        'route' => $route,
        'language' => $language,
        'answers' => $validatedAnswers,
    ];
}

function save_submission(mysqli $db, array $submission)
{
    $db->begin_transaction();

    try {
        $responseStmt = $db->prepare(
            'INSERT INTO survey_responses (respondent_name, gender_key, gender_other, age_years, role_key, role_other, route_key, preferred_language)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $responseStmt->bind_param(
            'sssissss',
            $submission['name'],
            $submission['gender'],
            $submission['gender_other'],
            $submission['age'],
            $submission['role'],
            $submission['role_other'],
            $submission['route'],
            $submission['language']
        );
        $responseStmt->execute();
        $responseId = $db->insert_id;
        $responseStmt->close();

        $optionStmt = $db->prepare(
            'INSERT INTO survey_answers (response_id, question_key, answer_type, option_key)
             VALUES (?, ?, ?, ?)'
        );
        $scaleStmt = $db->prepare(
            'INSERT INTO survey_answers (response_id, question_key, answer_type, option_key, numeric_value)
             VALUES (?, ?, ?, ?, ?)'
        );
        $textStmt = $db->prepare(
            'INSERT INTO survey_answers (response_id, question_key, answer_type, answer_text)
             VALUES (?, ?, ?, ?)'
        );

        $specs = survey_specs();
        foreach ($submission['answers'] as $questionKey => $value) {
            $type = $specs[$questionKey]['type'];

            if ($type === 'single') {
                $answerType = 'single';
                $optionKey = $value;
                $optionStmt->bind_param('isss', $responseId, $questionKey, $answerType, $optionKey);
                $optionStmt->execute();
                continue;
            }

            if ($type === 'multi') {
                $answerType = 'multi';
                foreach ($value as $optionKey) {
                    $optionStmt->bind_param('isss', $responseId, $questionKey, $answerType, $optionKey);
                    $optionStmt->execute();
                }
                continue;
            }

            if ($type === 'scale') {
                $answerType = 'scale';
                $optionKey = (string) $value;
                $numericValue = (float) $value;
                $scaleStmt->bind_param('isssd', $responseId, $questionKey, $answerType, $optionKey, $numericValue);
                $scaleStmt->execute();
                continue;
            }

            if ($type === 'text') {
                $answerType = 'text';
                $answerText = $value;
                $textStmt->bind_param('isss', $responseId, $questionKey, $answerType, $answerText);
                $textStmt->execute();
            }
        }

        $optionStmt->close();
        $scaleStmt->close();
        $textStmt->close();
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollback();
        respond(500, [
            'success' => false,
            'message' => 'Unable to save survey response.',
            'error' => $exception->getMessage(),
        ]);
    }
}

function build_age_buckets()
{
    return [
        'under_18' => 0,
        'age_18_24' => 0,
        'age_25_34' => 0,
        'age_35_44' => 0,
        'age_45_plus' => 0,
    ];
}

function age_bucket_key($age)
{
    if ($age < 18) {
        return 'under_18';
    }
    if ($age <= 24) {
        return 'age_18_24';
    }
    if ($age <= 34) {
        return 'age_25_34';
    }
    if ($age <= 44) {
        return 'age_35_44';
    }

    return 'age_45_plus';
}

function build_stats(mysqli $db)
{
    $stats = [
        'generatedAt' => date('c'),
        'totalResponses' => 0,
        'routeCounts' => ['parent' => 0, 'teacher' => 0, 'general' => 0],
        'roleCounts' => [],
        'genderCounts' => [],
        'ageSummary' => [
            'average' => 0,
            'min' => null,
            'max' => null,
            'buckets' => build_age_buckets(),
        ],
        'submissionsByDate' => [],
        'questions' => [],
        'responses' => [],
    ];

    $ageTotal = 0;
    $questionRespondents = [];
    $responseLookup = [];

    $responses = $db->query(
        'SELECT id, respondent_name, gender_key, gender_other, age_years, role_key, role_other, route_key, preferred_language, submitted_at
         FROM survey_responses
         ORDER BY submitted_at DESC, id DESC'
    );

    while ($row = $responses->fetch_assoc()) {
        $responseId = (int) $row['id'];
        $stats['totalResponses']++;

        $responseLookup[$responseId] = [
            'id' => $responseId,
            'name' => $row['respondent_name'],
            'genderKey' => $row['gender_key'],
            'genderOther' => $row['gender_other'],
            'age' => (int) $row['age_years'],
            'roleKey' => $row['role_key'],
            'roleOther' => $row['role_other'],
            'route' => $row['route_key'],
            'language' => $row['preferred_language'],
            'submittedAt' => $row['submitted_at'],
            'answers' => [],
        ];

        if (!isset($stats['routeCounts'][$row['route_key']])) {
            $stats['routeCounts'][$row['route_key']] = 0;
        }
        $stats['routeCounts'][$row['route_key']]++;

        if (!isset($stats['roleCounts'][$row['role_key']])) {
            $stats['roleCounts'][$row['role_key']] = 0;
        }
        $stats['roleCounts'][$row['role_key']]++;

        if (!isset($stats['genderCounts'][$row['gender_key']])) {
            $stats['genderCounts'][$row['gender_key']] = 0;
        }
        $stats['genderCounts'][$row['gender_key']]++;

        $age = (int) $row['age_years'];
        $ageTotal += $age;
        $bucketKey = age_bucket_key($age);
        $stats['ageSummary']['buckets'][$bucketKey]++;

        if ($stats['ageSummary']['min'] === null || $age < $stats['ageSummary']['min']) {
            $stats['ageSummary']['min'] = $age;
        }
        if ($stats['ageSummary']['max'] === null || $age > $stats['ageSummary']['max']) {
            $stats['ageSummary']['max'] = $age;
        }

        $dateKey = substr($row['submitted_at'], 0, 10);
        if (!isset($stats['submissionsByDate'][$dateKey])) {
            $stats['submissionsByDate'][$dateKey] = 0;
        }
        $stats['submissionsByDate'][$dateKey]++;
    }
    $responses->close();

    if ($stats['totalResponses'] > 0) {
        $stats['ageSummary']['average'] = round($ageTotal / $stats['totalResponses'], 1);
    }

    $answers = $db->query(
        'SELECT response_id, question_key, answer_type, option_key, answer_text, numeric_value
         FROM survey_answers
         ORDER BY id ASC'
    );

    while ($row = $answers->fetch_assoc()) {
        $questionKey = $row['question_key'];
        $responseId = (int) $row['response_id'];
        $answerType = $row['answer_type'];

        if (!isset($stats['questions'][$questionKey])) {
            $stats['questions'][$questionKey] = [
                'type' => $answerType,
                'optionCounts' => [],
                'responseCount' => 0,
                'selectionCount' => 0,
                'textResponses' => [],
                'average' => null,
                'scaleTotal' => 0,
                'scaleCount' => 0,
            ];
        }

        if (!isset($questionRespondents[$questionKey])) {
            $questionRespondents[$questionKey] = [];
        }
        $questionRespondents[$questionKey][$responseId] = true;

        if (($answerType === 'single' || $answerType === 'multi') && $row['option_key'] !== null) {
            if (!isset($stats['questions'][$questionKey]['optionCounts'][$row['option_key']])) {
                $stats['questions'][$questionKey]['optionCounts'][$row['option_key']] = 0;
            }
            $stats['questions'][$questionKey]['optionCounts'][$row['option_key']]++;

            if ($answerType === 'multi') {
                $stats['questions'][$questionKey]['selectionCount']++;
            }
        }

        if ($answerType === 'scale') {
            $scaleKey = $row['option_key'] !== null ? $row['option_key'] : (string) ((int) $row['numeric_value']);
            if (!isset($stats['questions'][$questionKey]['optionCounts'][$scaleKey])) {
                $stats['questions'][$questionKey]['optionCounts'][$scaleKey] = 0;
            }
            $stats['questions'][$questionKey]['optionCounts'][$scaleKey]++;
            $stats['questions'][$questionKey]['scaleTotal'] += (float) $row['numeric_value'];
            $stats['questions'][$questionKey]['scaleCount']++;
        }

        if ($answerType === 'text') {
            $text = trim((string) $row['answer_text']);
            if ($text !== '') {
                $lookup = isset($responseLookup[$responseId]) ? $responseLookup[$responseId] : [
                    'name' => 'Anonymous',
                    'route' => 'unknown',
                    'submittedAt' => null,
                ];

                $stats['questions'][$questionKey]['textResponses'][] = [
                    'responseId' => $responseId,
                    'name' => $lookup['name'],
                    'route' => $lookup['route'],
                    'submittedAt' => $lookup['submittedAt'],
                    'text' => $text,
                ];
            }
        }

        if (isset($responseLookup[$responseId])) {
            if ($answerType === 'multi') {
                if (!isset($responseLookup[$responseId]['answers'][$questionKey])) {
                    $responseLookup[$responseId]['answers'][$questionKey] = [];
                }
                $responseLookup[$responseId]['answers'][$questionKey][] = $row['option_key'];
            } elseif ($answerType === 'text') {
                $responseLookup[$responseId]['answers'][$questionKey] = trim((string) $row['answer_text']);
            } elseif ($answerType === 'scale') {
                $responseLookup[$responseId]['answers'][$questionKey] = $row['option_key'] !== null
                    ? $row['option_key']
                    : (string) ((int) $row['numeric_value']);
            } else {
                $responseLookup[$responseId]['answers'][$questionKey] = $row['option_key'];
            }
        }
    }
    $answers->close();

    foreach ($stats['questions'] as $questionKey => $questionData) {
        $stats['questions'][$questionKey]['responseCount'] = isset($questionRespondents[$questionKey])
            ? count($questionRespondents[$questionKey])
            : 0;

        if ($questionData['type'] === 'scale' && $questionData['scaleCount'] > 0) {
            $stats['questions'][$questionKey]['average'] = round(
                $questionData['scaleTotal'] / $questionData['scaleCount'],
                2
            );
        }

        unset($stats['questions'][$questionKey]['scaleTotal'], $stats['questions'][$questionKey]['scaleCount']);
    }

    foreach ($responseLookup as $response) {
        $stats['responses'][] = $response;
    }

    ksort($stats['submissionsByDate']);

    return $stats;
}

function require_admin_login()
{
    if (empty($_SESSION['admin_logged_in'])) {
        respond(401, [
            'success' => false,
            'message' => 'Admin login required.',
        ]);
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action === '' && isset($_POST['action'])) {
    $action = (string) $_POST['action'];
}

try {
    if ($action === 'submit') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['success' => false, 'message' => 'Method not allowed.']);
        }

        $payload = read_json_input();
        $submission = validate_submission($payload);
        save_submission(get_db(), $submission);

        respond(200, [
            'success' => true,
            'message' => 'Survey submitted successfully.',
        ]);
    }

    if ($action === 'admin_login') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['success' => false, 'message' => 'Method not allowed.']);
        }

        $payload = read_json_input();
        $username = trim((string) (isset($payload['username']) ? $payload['username'] : ''));
        $password = trim((string) (isset($payload['password']) ? $payload['password'] : ''));

        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            respond(200, [
                'success' => true,
                'message' => 'Admin login successful.',
            ]);
        }

        respond(401, [
            'success' => false,
            'message' => 'Invalid admin username or password.',
        ]);
    }

    if ($action === 'admin_logout') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(405, ['success' => false, 'message' => 'Method not allowed.']);
        }

        $_SESSION = [];
        if (session_id() !== '') {
            session_destroy();
        }

        respond(200, [
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    if ($action === 'get_stats') {
        require_admin_login();
        respond(200, [
            'success' => true,
            'stats' => build_stats(get_db()),
        ]);
    }

    respond(404, [
        'success' => false,
        'message' => 'Unknown action.',
    ]);
} catch (Throwable $exception) {
    respond(500, [
        'success' => false,
        'message' => 'Server error.',
        'error' => $exception->getMessage(),
    ]);
}
