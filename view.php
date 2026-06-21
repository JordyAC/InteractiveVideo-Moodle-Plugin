<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Main activity page for mod_vidinteractivo.
 *
 * @package    mod_vidinteractivo
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('vidinteractivo', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $n = required_param('n', PARAM_INT);
    $vidinteractivo = $DB->get_record('vidinteractivo', ['id' => $n], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $vidinteractivo->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('vidinteractivo', $vidinteractivo->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/vidinteractivo:view', $context);

$event = \mod_vidinteractivo\event\course_module_viewed::create([
    'objectid' => $vidinteractivo->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('vidinteractivo', $vidinteractivo);
$event->trigger();

$PAGE->set_url('/mod/vidinteractivo/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($vidinteractivo->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/vidinteractivo/styles.css');

$canauthor = has_capability('mod/vidinteractivo:author', $context);
$canviewreports = has_capability('mod/vidinteractivo:viewreports', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($vidinteractivo->name), 2);

if (!empty($vidinteractivo->intro)) {
    echo $OUTPUT->box(format_module_intro('vidinteractivo', $vidinteractivo, $cm->id), 'generalbox mod_introbox', 'intro');
}
?>
<?php
$videourl = $vidinteractivo->videourl;
if (isset($vidinteractivo->videotype) && $vidinteractivo->videotype === 'file') {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_vidinteractivo', 'video', 0, 'sortorder DESC, id ASC', false);
    if (!empty($files)) {
        $file = reset($files);
        $videourl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename())->out(false);
    }
}

$isyoutube = false;
$youtubeid = '';
if (strpos((string)$videourl, 'youtube.com') !== false || strpos((string)$videourl, 'youtu.be') !== false) {
    $isyoutube = true;
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', (string)$videourl, $match)) {
        $youtubeid = $match[1];
    }
}
?>
<div class="vidinteractivo-main-container container-fluid py-4">
    <div class="row">
        <div class="col-lg-9 mx-auto">
            <div class="card shadow-sm border-0 bg-dark text-white rounded-3 overflow-hidden position-relative">
                <div class="vidinteractivo-player-wrapper text-center">
                    <?php if ($isyoutube && !empty($youtubeid)): ?>
                        <div id="vidinteractivo-youtube" data-youtubeid="<?php echo s($youtubeid); ?>" class="w-100 d-block" style="aspect-ratio: 16/9; background: #000;"></div>
                    <?php else: ?>
                        <video id="vidinteractivo-video" class="w-100 d-block" controls controlsList="nodownload">
                            <source src="<?php echo s($videourl); ?>" type="video/mp4">
                            Your browser does not support the video element.
                        </video>
                    <?php endif; ?>
                    <div class="vidinteractivo-progressbar-container" style="z-index: 10;">
                        <div class="vidinteractivo-progressbar"></div>
                    </div>
                </div>
                <?php
                echo $OUTPUT->render_from_template('mod_vidinteractivo/interaction_overlay', [
                    'uniqid' => uniqid('', false),
                ]);
                ?>
            </div>

            <?php if ($canauthor): ?>
                <div class="teacher-controls mt-4 p-3 bg-light border rounded">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0 text-dark">Panel de autoria</h4>
                        <div>
                            <?php if ($canviewreports): ?>
                                <a class="btn btn-outline-secondary btn-md" href="<?php echo (new moodle_url('/mod/vidinteractivo/report.php', ['id' => $cm->id]))->out(false); ?>">
                                    <?php echo get_string('report', 'mod_vidinteractivo'); ?>
                                </a>
                            <?php endif; ?>
                            <button id="vidinteractivo-add-marker" class="btn btn-primary btn-md">
                                <?php echo get_string('addinteraction', 'mod_vidinteractivo'); ?>
                            </button>
                        </div>
                    </div>
                    <div id="vidinteractivo-authoring-list" class="table-responsive"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$selector = ($isyoutube && !empty($youtubeid)) ? '#vidinteractivo-youtube' : '#vidinteractivo-video';
if ($canauthor) {
    $PAGE->requires->js_call_amd('mod_vidinteractivo/timeline', 'init', [$selector, $cm->id]);
}
$PAGE->requires->js_call_amd('mod_vidinteractivo/player', 'init', [$selector, $cm->id]);

if ($isyoutube && !empty($youtubeid)) {
    // Load YouTube API script manually if not loaded, though VideoAdapter will do it if needed.
    // However, loading it securely here is fine, but it's better done in AMD.
}

echo $OUTPUT->footer();
