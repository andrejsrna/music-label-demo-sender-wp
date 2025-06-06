<?php
/**
 * Debug Media Library Functionality
 *
 * This script helps debug media library selection issues.
 * Access it via: http://your-site.com/wp-content/plugins/music-label-demo-sender/debug-media-library.php
 *
 * Usage: http://localhost:8888/wp-content/plugins/music-label-demo-sender/debug-media-library.php
 */

// Load WordPress
if (!defined('ABSPATH')) {
	// Try different paths to find wp-load.php
	$wp_load_paths = [
		'../../../wp-load.php', // Standard plugin location
		'../../../../wp-load.php', // Alternative location
		'../../../wp-config.php', // Fallback to wp-config
	];

	$wp_loaded = false;
	foreach ($wp_load_paths as $path) {
		if (file_exists(dirname(__FILE__) . '/' . $path)) {
			require_once dirname(__FILE__) . '/' . $path;
			$wp_loaded = true;
			break;
		}
	}

	if (!$wp_loaded) {
		die('Could not load WordPress. Make sure this script is placed in the plugin directory.');
	}
}

// Check if user is admin
if (!current_user_can('manage_options')) {
	die('Access denied. You must be an administrator to run this script.');
}

// Get plugin info
$plugin_dir = plugin_dir_path(__FILE__);
$plugin_url = plugin_dir_url(__FILE__);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Media Library Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .test-section { 
            background: #f9f9f9; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px; 
        }
        .result { 
            background: #fff; 
            padding: 10px; 
            margin: 10px 0; 
            border-left: 4px solid #0073aa; 
        }
        .error { border-left-color: #dc3232; }
        .success { border-left-color: #46b450; }
        button { 
            background: #0073aa; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
            margin: 5px;
        }
        button:hover { background: #005a87; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Media Library Debug Test</h1>
        <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
        <p><strong>Plugin Directory:</strong> <?php echo $plugin_dir; ?></p>
        
        <div class="test-section">
            <h2>1. WordPress Dependencies Check</h2>
            <div class="result">
                <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?>
            </div>
            <div class="result">
                <strong>wp_enqueue_media() available:</strong> <?php echo function_exists(
                	'wp_enqueue_media',
                )
                	? '✅ Yes'
                	: '❌ No'; ?>
            </div>
            <div class="result">
                <strong>wp.media available in JS:</strong> <span id="wp-media-check">🔄 Checking...</span>
            </div>
            <div class="result">
                <strong>jQuery available:</strong> <span id="jquery-check">🔄 Checking...</span>
            </div>
        </div>

        <div class="test-section">
            <h2>2. Plugin Files Check</h2>
            <div class="result">
                <?php
                $js_file = $plugin_dir . 'js/mlds-admin-media-select.js';
                $js_url = $plugin_url . 'js/mlds-admin-media-select.js';
                echo '<strong>JavaScript file exists:</strong> ' .
                	(file_exists($js_file) ? '✅ Yes' : '❌ No');
                echo '<br><strong>File path:</strong> <code>' . $js_file . '</code>';
                echo '<br><strong>File URL:</strong> <code>' . $js_url . '</code>';

                if (file_exists($js_file)) {
                	echo '<br><strong>File size:</strong> ' . filesize($js_file) . ' bytes';
                	echo '<br><strong>Last modified:</strong> ' .
                		date('Y-m-d H:i:s', filemtime($js_file));
                }
                ?>
            </div>
            
            <div class="result">
                <?php // Check if plugin is active

if (is_plugin_active('music-label-demo-sender/music-label-demo-sender.php')) {
                	echo '✅ <strong>Plugin is active</strong>';
                } else {
                	echo '❌ <strong>Plugin is NOT active</strong>';
                } ?>
            </div>
        </div>

        <div class="test-section">
            <h2>3. Basic Media Library Test</h2>
            <button type="button" id="test-media-button">🎵 Test Media Library Selection</button>
            <div id="test-results" class="result" style="margin-top: 10px; display: none;">
                <p>Media selection results will appear here...</p>
            </div>
        </div>

        <div class="test-section">
            <h2>4. Plugin Script Test</h2>
            <p>This tests the actual plugin functionality:</p>
            <button type="button" id="mlds-select-media-tracks-button">🎶 Plugin Media Selection Button</button>
            <div id="mlds-selected-media-tracks-display" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px;"></div>
            <div id="mlds-media-library-track-ids-container"></div>
        </div>

        <div class="test-section">
            <h2>5. Network Test</h2>
            <button type="button" id="test-script-loading">🌐 Test Script Loading</button>
            <div id="network-results" class="result" style="margin-top: 10px; display: none;"></div>
        </div>

        <div class="test-section">
            <h2>6. Console Debug Info</h2>
            <div class="result">
                <p>📋 <strong>Instructions:</strong></p>
                <ol>
                    <li>Open your browser's Developer Tools (F12)</li>
                    <li>Go to the Console tab</li>
                    <li>Click the test buttons above</li>
                    <li>Check for any JavaScript errors or warnings</li>
                </ol>
            </div>
        </div>
    </div>

    <?php
    // Enqueue WordPress media scripts
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_footer();
    ?>

    <script>
        jQuery(document).ready(function($) {
            console.log('🚀 Debug script initialized');
            
            // Check if dependencies are available
            $('#jquery-check').html(typeof jQuery !== 'undefined' ? '✅ Yes (v' + jQuery.fn.jquery + ')' : '❌ No');
            $('#wp-media-check').html(typeof wp !== 'undefined' && typeof wp.media !== 'undefined' ? '✅ Yes' : '❌ No');

            // Test basic media library functionality
            $('#test-media-button').on('click', function(e) {
                e.preventDefault();
                console.log('🧪 Testing basic media library...');
                
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    $('#test-results').show().html('<p style="color: red;">❌ ERROR: wp.media is not available!</p>');
                    console.error('wp.media is not available');
                    return;
                }

                var frame = wp.media({
                    title: 'Test Media Selection',
                    button: { text: 'Select Audio Files' },
                    library: { type: 'audio' },
                    multiple: true
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    var result = '<p style="color: green;">✅ SUCCESS! Selected ' + selection.length + ' audio file(s):</p><ul>';
                    
                    selection.each(function(attachment) {
                        var props = attachment.toJSON();
                        result += '<li><strong>' + (props.title || props.filename) + '</strong> (ID: ' + props.id + ', Type: ' + props.subtype + ')</li>';
                    });
                    
                    result += '</ul>';
                    $('#test-results').show().html(result);
                    console.log('✅ Media selection successful:', selection);
                });

                frame.on('open', function() {
                    console.log('📂 Media library opened');
                });

                frame.open();
            });

            // Test script loading
            $('#test-script-loading').on('click', function() {
                console.log('🌐 Testing script loading...');
                $('#network-results').show().html('<p>🔄 Testing script loading...</p>');
                
                $.get('<?php echo $js_url; ?>')
                    .done(function(data) {
                        $('#network-results').html('<p style="color: green;">✅ Script loaded successfully (' + data.length + ' characters)</p>');
                        console.log('✅ Script loading test passed');
                        
                        // Try to execute the script
                        try {
                            eval(data);
                            $('#network-results').append('<p style="color: green;">✅ Script executed without errors</p>');
                        } catch(err) {
                            $('#network-results').append('<p style="color: red;">❌ Script execution error: ' + err.message + '</p>');
                            console.error('Script execution error:', err);
                        }
                    })
                    .fail(function(xhr, status, error) {
                        $('#network-results').html('<p style="color: red;">❌ Failed to load script: ' + error + '</p>');
                        console.error('Script loading failed:', error);
                    });
            });

            // Load the original plugin script
            console.log('📦 Loading plugin media selection script...');
            var scriptUrl = '<?php echo $js_url; ?>';
            console.log('Script URL:', scriptUrl);
            
            $.getScript(scriptUrl)
                .done(function() {
                    console.log('✅ Plugin script loaded successfully');
                })
                .fail(function(jqxhr, settings, exception) {
                    console.error('❌ Failed to load plugin script:', exception);
                });
        });
    </script>
</body>
</html> 