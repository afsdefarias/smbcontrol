<?php
namespace App\Services;

class SambaParser {
    
    /**
     * Parse smb.conf content into structured array
     */
    public static function parse(string $content): array {
        $parsedConf = [];
        $currentSection = 'global';
        $bufferComments = [];

        foreach (explode("\n", $content) as $line) {
            $trimmed = trim($line);
            
            // Ignore empty lines
            if ($trimmed === '') {
                continue;
            }

            // text comments starting with #
            if ($trimmed[0] === '#') {
                $bufferComments[] = ltrim(substr($trimmed, 1));
                continue;
            }

            // disabled param or section starting with ;
            $disabled = false;
            if ($trimmed[0] === ';') {
                $disabled = true;
                $trimmed = trim(substr($trimmed, 1)); // remove ;
            }

            // Sections
            if (preg_match('/^\[(.*)\]$/', $trimmed, $matches)) {
                if (!empty($bufferComments)) {
                    if (!isset($parsedConf[$currentSection])) $parsedConf[$currentSection] = [];
                    $parsedConf[$currentSection][] = [
                        'is_standalone_comment' => true,
                        'comments' => implode("\n", $bufferComments)
                    ];
                    $bufferComments = [];
                }
                $currentSection = $matches[1];
                if (!isset($parsedConf[$currentSection])) {
                    $parsedConf[$currentSection] = [];
                }
                continue;
            } 
            
            // Param key = value
            if (strpos($trimmed, '=') !== false) {
                list($key, $val) = explode('=', $trimmed, 2);
                $key = trim($key);
                $val = trim($val);
                
                $isBoolean = in_array(strtolower($val), ['yes', 'no']);

                $parsedConf[$currentSection][] = [
                    'key' => $key,
                    'value' => $val,
                    'disabled' => $disabled,
                    'is_boolean' => $isBoolean,
                    'comments' => implode("\n", $bufferComments)
                ];
                $bufferComments = []; // reset for next param
            }
        }
        
        if (!empty($bufferComments)) {
            if (!isset($parsedConf[$currentSection])) $parsedConf[$currentSection] = [];
            $parsedConf[$currentSection][] = [
                'is_standalone_comment' => true,
                'comments' => implode("\n", $bufferComments)
            ];
        }
        
        return $parsedConf;
    }

    /**
     * Generate smb.conf text from structured array
     */
    public static function generate(array $configData): string {
        $content = "";
        foreach ($configData as $section => $fields) {
            // Check if section actually has valid fields
            if (!is_array($fields)) continue;
            
            $content .= "[$section]\n";
            
            foreach ($fields as $field) {
                if (!empty($field['is_standalone_comment'])) {
                    if (!empty($field['comments'])) {
                        $comments = explode("\n", $field['comments']);
                        foreach ($comments as $c) {
                            $content .= "# " . trim($c) . "\n";
                        }
                    }
                    continue;
                }
                
                if (empty($field['key'])) continue;
                
                $key = $field['key'];
                $val = $field['value'] ?? '';
                
                // Enabled checkbox: 1 = active, 0 = disabled (;). Parsed data does
                // not contain "enabled", so fall back to the inverse of "disabled".
                $enabled = array_key_exists('enabled', $field)
                    ? $field['enabled'] == '1'
                    : empty($field['disabled']);
                $prefix = $enabled ? "   " : ";   ";

                // Put comments back
                if (!empty($field['comments'])) {
                    $comments = explode("\n", $field['comments']);
                    foreach ($comments as $c) {
                        $content .= "# " . trim($c) . "\n";
                    }
                }

                $content .= "{$prefix}{$key} = {$val}\n";
            }
            $content .= "\n";
        }
        return $content;
    }
}
