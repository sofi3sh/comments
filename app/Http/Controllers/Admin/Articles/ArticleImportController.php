<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Controller;
use App\Services\Article\ArticleImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArticleImportController extends Controller
{
    protected ArticleImportService $importService;

    public function __construct(ArticleImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show import form
     *
     * @return \Illuminate\View\View
     */
    public function showImportForm()
    {
        $logFile = storage_path('logs/import_debug.log');
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " === showImportForm CALLED ===\n", FILE_APPEND | LOCK_EX);
        @error_log('=== showImportForm CALLED ===');
        
        return view('vendor.backpack.article.import');
    }

    /**
     * Process import
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function processImport(Request $request)
    {
        // Force immediate logging test - multiple methods
        $logFile = storage_path('logs/import_debug.log');
        $logsDir = storage_path('logs');
        
        // Check if logs directory is writable
        $logsWritable = is_writable($logsDir);
        $logFileWritable = is_writable($logFile) || (!file_exists($logFile) && is_writable($logsDir));
        
        // Try to create log file with different methods
        $writeSuccess = false;
        
        // Method 1: Direct file_put_contents
        try {
            $result = @file_put_contents($logFile, date('Y-m-d H:i:s') . " === IMPORT METHOD CALLED ===\n", FILE_APPEND | LOCK_EX);
            if ($result !== false) {
                $writeSuccess = true;
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        // Method 2: error_log
        @error_log('=== IMPORT METHOD CALLED (error_log) ===');
        
        // Method 3: Try to write to PHP error log directly
        ini_set('log_errors', 1);
        ini_set('error_log', $logFile);
        @error_log('=== IMPORT METHOD CALLED (via ini_set) ===');
        
        // Write diagnostic info
        $diag = [
            'logs_dir_exists' => is_dir($logsDir) ? 'YES' : 'NO',
            'logs_dir_writable' => $logsWritable ? 'YES' : 'NO',
            'log_file_exists' => file_exists($logFile) ? 'YES' : 'NO',
            'log_file_writable' => $logFileWritable ? 'YES' : 'NO',
            'write_success' => $writeSuccess ? 'YES' : 'NO',
            'request_method' => $request->method(),
            'limit' => $request->input('limit', 'NOT SET'),
        ];
        
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " DIAGNOSTIC: " . json_encode($diag) . "\n", FILE_APPEND | LOCK_EX);
        @error_log('DIAGNOSTIC: ' . json_encode($diag));
        
        // Test all log channels
        try {
            Log::channel('single')->info('=== IMPORT METHOD CALLED (single channel) ===');
            Log::info('=== IMPORT METHOD CALLED (default channel) ===');
        } catch (\Exception $e) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " Log::info error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            @error_log('Log::info error: ' . $e->getMessage());
        }
        
        try {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " Starting validation...\n", FILE_APPEND | LOCK_EX);
            @error_log('Starting validation...');
            
            // Validate based on import type
            $importType = $request->input('import_type', 'db');
            
            if ($importType === 'db') {
                // Import from database
                $validated = $request->validate([
                    'import_type' => 'required|in:db',
                    'limit' => 'nullable|integer|min:1|max:100000',
                    'batch_limit' => 'nullable|integer|min:1|max:1000',
                ]);
                
                $importStartTime = microtime(true);
                $userId = backpack_user()->id ?? 0;
                
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [IMPORT] [CONTROLLER] Початок імпорту з БД\n", FILE_APPEND | LOCK_EX);
                Log::info('[IMPORT] [CONTROLLER] Початок імпорту з БД', [
                    'limit' => $validated['limit'] ?? null,
                    'batch_limit' => $validated['batch_limit'] ?? 100,
                ]);
                
                // Prepare log data
                $logData = [
                    'user_id' => $userId,
                    'limit' => $validated['limit'] ?? null,
                    'batch_limit' => $validated['batch_limit'] ?? 100,
                    'timestamp' => now()->toDateTimeString(),
                ];
                
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [IMPORT] [CONTROLLER] Дані: " . json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
                Log::info('[IMPORT] [CONTROLLER] Початок імпорту статей з БД', $logData);
                
                // Import articles from database
                $importStart = microtime(true);
                $stats = $this->importService->importArticlesFromDb(
                    $validated['batch_limit'] ?? 100,
                    $validated['limit'] ?? null
                );
                $importTime = round((microtime(true) - $importStart), 2);
                
                $parseTime = 0; // No parsing needed
                $mappingTime = 0; // Included in import time
                
            } else {
                // Only DB import is supported now
                throw new \Exception('Імпорт з файлу більше не підтримується. Використовуйте імпорт з бази даних (тип: db).');
            }
            
            Log::info('[IMPORT] [CONTROLLER] Імпорт статей завершено', [
                'imported' => $stats['imported'],
                'skipped' => $stats['skipped'],
                'time_sec' => $importTime,
            ]);

            $totalTime = round((microtime(true) - $importStartTime), 2);
            
            Log::info('[IMPORT] [CONTROLLER] ===== Весь процес імпорту завершено =====', [
                'imported' => $stats['imported'],
                'skipped' => $stats['skipped'],
                'total_time_sec' => $totalTime,
                'breakdown' => [
                    'parse_sec' => $parseTime,
                    'mapping_sec' => $mappingTime,
                    'import_sec' => $importTime,
                ],
            ]);
            Log::info('[IMPORT] [CONTROLLER] ========================================');

            // Return success message
            $response = redirect()
                ->back()
                ->with('success', __('article.import.success', [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                ]));
            
            // If AJAX request, return JSON response
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('article.import.success', [
                        'imported' => $stats['imported'],
                        'skipped' => $stats['skipped'],
                    ]),
                    'redirect' => url()->previous()
                ]);
            }
            
            return $response;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $logFile = storage_path('logs/import_debug.log');
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " VALIDATION ERROR: " . json_encode($e->errors()) . "\n", FILE_APPEND | LOCK_EX);
            @error_log('VALIDATION ERROR: ' . json_encode($e->errors()));
            
            Log::error('[IMPORT] [CONTROLLER] Validation error', [
                'errors' => $e->errors(),
            ]);

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            $logFile = storage_path('logs/import_debug.log');
            $totalTime = isset($importStartTime) ? round((microtime(true) - $importStartTime), 2) : 0;
            
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " File: " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX);
            @error_log('EXCEPTION: ' . $e->getMessage());
            
            Log::error('[IMPORT] [CONTROLLER] ===== ПОМИЛКА ІМПОРТУ =====', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'total_time_sec' => $totalTime,
                'trace' => $e->getTraceAsString(),
            ]);
            Log::error('[IMPORT] [CONTROLLER] ========================================');

            // If AJAX request, return JSON response
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('article.import.error', ['message' => $e->getMessage()]),
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect()
                ->back()
                ->with('error', __('article.import.error', ['message' => $e->getMessage()]))
                ->withInput();
        }
    }

}

