<?php

/**
 * Label Manager Integration Test
 * 
 * Testet das komplette Label-System:
 * - System-Labels Initialisierung
 * - Custom Labels CRUD
 * - Thread-Label Zuweisungen
 * - Label-Filterung
 * - System-Label Schutz
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Services\LabelService;
use CiInbox\App\Repositories\LabelRepository;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\Modules\Label\LabelManager;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== Label Manager Integration Test ===" . PHP_EOL . PHP_EOL;

try {
    // 1. Application & Services initialisieren
    echo "1. Initializing services..." . PHP_EOL;
    
    // Services
    $logger = new LoggerService(__DIR__ . '/../../../../logs');
    $config = new ConfigService(
        envPath: __DIR__ . '/../../../../',
        configPath: __DIR__ . '/../../../../src/config',
        logger: $logger
    );
    
    // Initialize Database
    require_once __DIR__ . '/../../../../src/bootstrap/database.php';
    initDatabase($config);
    
    // Label-Config manuell laden
    $labelConfig = require __DIR__ . '/../config/label.config.php';
    
    // Repositories
    $labelRepository = new LabelRepository();
    $threadRepository = new ThreadRepository($logger);
    
    // Manager & Services
    $labelManager = new LabelManager($labelRepository, $logger, $labelConfig);
    $labelService = new LabelService($labelManager, $labelRepository, $threadRepository, $logger);
    
    echo "✓ Services initialized" . PHP_EOL . PHP_EOL;
    
    // 2. Alte Test-Daten löschen
    echo "2. Clearing old test data..." . PHP_EOL;
    
    // Alle Labels löschen (außer die, die wir gleich neu erstellen)
    $existingLabels = $labelRepository->getAll();
    foreach ($existingLabels as $label) {
        if (!$label->is_system_label) {
            $labelRepository->delete($label->id);
        }
    }
    
    echo "✓ Old data cleared" . PHP_EOL . PHP_EOL;
    
    // 3. System-Labels initialisieren
    echo "3. Initializing system labels..." . PHP_EOL;
    
    $createdIds = $labelService->initializeSystemLabels();
    $systemLabels = $labelService->getAllLabels(systemOnly: true);
    
    echo "✓ Created " . count($createdIds) . " new system labels" . PHP_EOL;
    echo "✓ Total system labels: " . count($systemLabels) . PHP_EOL;
    
    foreach ($systemLabels as $label) {
        echo "  - {$label->name} ({$label->color})" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // 4. Custom Labels erstellen
    echo "4. Creating custom labels..." . PHP_EOL;
    
    $customLabels = [
        ['name' => 'Projekt Alpha', 'color' => '#FF5733'],
        ['name' => 'Team Meeting', 'color' => '#33C4FF'],
        ['name' => 'Urgent', 'color' => '#FF0000'],
        ['name' => 'Personal', 'color' => '#00FF00']
    ];
    
    $customLabelIds = [];
    foreach ($customLabels as $labelData) {
        try {
            $labelId = $labelService->createLabel($labelData['name'], $labelData['color']);
            $customLabelIds[] = $labelId;
            echo "✓ Created label: {$labelData['name']} (ID: {$labelId})" . PHP_EOL;
        } catch (Exception $e) {
            echo "✗ Failed to create label: {$labelData['name']} - {$e->getMessage()}" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    
    // 5. Labels zu Threads zuweisen
    echo "5. Tagging threads with labels..." . PHP_EOL;
    
    // Threads aus DB holen
    $threads = $threadRepository->getAll();
    
    if (count($threads) < 2) {
        echo "⚠ Not enough threads in database (need at least 2)" . PHP_EOL;
        echo "  Please run thread-manager-integration-test.php first" . PHP_EOL . PHP_EOL;
    } else {
        $thread1 = $threads[0];
        $thread2 = $threads[1];
        
        // Inbox Label holen
        $inboxLabel = $labelService->getLabelByName('Inbox');
        $starredLabel = $labelService->getLabelByName('Starred');
        
        if ($inboxLabel && count($customLabelIds) >= 2) {
            // Thread 1: Inbox + Projekt Alpha
            $labelService->tagThread($thread1->id, $inboxLabel->id);
            $labelService->tagThread($thread1->id, $customLabelIds[0]);
            echo "✓ Tagged Thread #{$thread1->id} with 'Inbox' and 'Projekt Alpha'" . PHP_EOL;
            
            // Thread 2: Inbox + Urgent + Starred
            $labelService->tagThread($thread2->id, $inboxLabel->id);
            $labelService->tagThread($thread2->id, $customLabelIds[2]);
            if ($starredLabel) {
                $labelService->tagThread($thread2->id, $starredLabel->id);
            }
            echo "✓ Tagged Thread #{$thread2->id} with 'Inbox', 'Urgent', 'Starred'" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    
    // 6. Threads nach Label filtern
    echo "6. Filtering threads by label..." . PHP_EOL;
    
    if (isset($inboxLabel)) {
        $result = $labelService->getThreadsByLabel($inboxLabel->id);
        echo "Label 'Inbox': {$result['total']} threads" . PHP_EOL;
        
        foreach ($result['threads'] as $thread) {
            $labels = $labelService->getThreadLabels($thread->id);
            $labelNames = array_map(fn($l) => $l->name, $labels);
            echo "  - Thread #{$thread->id}: {$thread->subject} [" . implode(', ', $labelNames) . "]" . PHP_EOL;
        }
    }
    
    if (!empty($customLabelIds)) {
        $projektLabel = $labelManager->getLabel($customLabelIds[0]);
        if ($projektLabel) {
            $result = $labelService->getThreadsByLabel($projektLabel->id);
            echo PHP_EOL . "Label '{$projektLabel->name}': {$result['total']} thread(s)" . PHP_EOL;
            
            foreach ($result['threads'] as $thread) {
                echo "  - Thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
            }
        }
    }
    echo PHP_EOL;
    
    // 7. Label-Statistik
    echo "7. Label statistics..." . PHP_EOL;
    
    $statistics = $labelService->getLabelStatistics();
    $totalLabels = count($statistics);
    $systemCount = count(array_filter($statistics, fn($s) => $s['is_system']));
    $customCount = $totalLabels - $systemCount;
    
    echo "Total Labels: {$totalLabels} ({$systemCount} system, {$customCount} custom)" . PHP_EOL;
    
    $threadsWithLabels = count(array_filter($statistics, fn($s) => $s['thread_count'] > 0));
    echo "Labels with threads: {$threadsWithLabels}" . PHP_EOL . PHP_EOL;
    
    echo "Top Labels:" . PHP_EOL;
    foreach (array_slice($statistics, 0, 5) as $stat) {
        $type = $stat['is_system'] ? 'system' : 'custom';
        echo "  - {$stat['label_name']} ({$type}): {$stat['thread_count']} thread(s)" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // 8. Label aktualisieren
    echo "8. Updating custom label..." . PHP_EOL;
    
    if (!empty($customLabelIds)) {
        $labelToUpdate = $customLabelIds[0];
        $success = $labelService->updateLabel($labelToUpdate, [
            'color' => '#00FF00',
            'display_order' => 100
        ]);
        
        if ($success) {
            $updated = $labelManager->getLabel($labelToUpdate);
            echo "✓ Updated label '{$updated->name}' - new color: {$updated->color}" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    
    // 9. Label löschen
    echo "9. Deleting custom label..." . PHP_EOL;
    
    if (!empty($customLabelIds) && count($customLabelIds) > 1) {
        $labelToDelete = $customLabelIds[1]; // Team Meeting
        $label = $labelManager->getLabel($labelToDelete);
        
        try {
            $success = $labelService->deleteLabel($labelToDelete);
            if ($success) {
                echo "✓ Deleted label: {$label->name}" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo "✗ Failed to delete label: {$e->getMessage()}" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    
    // 10. System-Label Schutz validieren
    echo "10. Validating system label protection..." . PHP_EOL;
    
    if (isset($inboxLabel)) {
        try {
            $labelService->deleteLabel($inboxLabel->id);
            echo "✗ ERROR: System label 'Inbox' was deleted (should be protected!)" . PHP_EOL;
        } catch (Exception $e) {
            echo "✓ System label 'Inbox' is protected: {$e->getMessage()}" . PHP_EOL;
        }
    }
    echo PHP_EOL;
    
    // 11. Batch-Tagging testen
    echo "11. Testing batch tagging..." . PHP_EOL;
    
    if (!empty($threads) && !empty($customLabelIds)) {
        $thread = $threads[count($threads) - 1];
        $labelsToTag = array_slice($customLabelIds, 0, 2);
        
        $result = $labelService->tagThreadBatch($thread->id, $labelsToTag);
        echo "✓ Batch tagged Thread #{$thread->id}" . PHP_EOL;
        echo "  Success: " . count($result['success']) . " labels" . PHP_EOL;
        echo "  Failed: " . count($result['failed']) . " labels" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // 12. Final Statistics
    echo "12. Final statistics..." . PHP_EOL;
    
    $allLabels = $labelService->getAllLabels();
    $systemLabels = $labelService->getAllLabels(systemOnly: true);
    $customLabels = $labelService->getAllLabels(systemOnly: false);
    
    echo "Total Labels: " . count($allLabels) . PHP_EOL;
    echo "System Labels: " . count($systemLabels) . PHP_EOL;
    echo "Custom Labels: " . count($customLabels) . PHP_EOL . PHP_EOL;
    
    // Thread-Label Assignments
    $totalAssignments = DB::table('thread_labels')->count();
    echo "Total Thread-Label Assignments: {$totalAssignments}" . PHP_EOL;
    
    echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
    echo "Check logs/app-" . date('Y-m-d') . ".log for detailed logging output" . PHP_EOL;
    
} catch (Exception $e) {
    echo PHP_EOL . "✗ Test failed with error:" . PHP_EOL;
    echo "  {$e->getMessage()}" . PHP_EOL;
    echo "  File: {$e->getFile()}:{$e->getLine()}" . PHP_EOL;
    echo PHP_EOL . "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
