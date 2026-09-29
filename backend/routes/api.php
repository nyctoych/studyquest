<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/QuestController.php';
require_once __DIR__ . '/../controllers/BossController.php';
require_once __DIR__ . '/../controllers/RewardController.php';
require_once __DIR__ . '/../controllers/AchievementController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../controllers/MultiplayerController.php';
require_once __DIR__ . '/../controllers/ShopController.php';
require_once __DIR__ . '/../controllers/QuestionController.php';
require_once __DIR__ . '/../controllers/CurriculumController.php';
require_once __DIR__ . '/../controllers/AdventureController.php';
require_once __DIR__ . '/../controllers/ChronicleController.php';
require_once __DIR__ . '/../controllers/SpecialEventController.php';
require_once __DIR__ . '/../controllers/SpecialEventAdminController.php';

function dispatch(string $method, string $path): void {
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    // ── SPECIAL EVENT (DAILY 140-QUESTION TRIAL) ──────────────
    if (!empty($segments) && $segments[0] === 'special-event') {
        $sub = $segments[1] ?? '';
        $id  = (isset($segments[2]) && is_numeric($segments[2])) ? (int)$segments[2] : null;

        // Admin sub-routes
        if ($sub === 'admin') {
            $admin = new SpecialEventAdminController();
            $act = $segments[2] ?? '';
            $subId = (isset($segments[3]) && is_numeric($segments[3])) ? (int)$segments[3] : null;

            if ($act === 'generate' && $method === 'POST') { $admin->generate(); return; }
            if ($act === 'events' && $method === 'GET' && !$subId) { $admin->events(); return; }
            if ($act === 'events' && $subId && isset($segments[4]) && $segments[4] === 'questions' && $method === 'GET') { $admin->questions($subId); return; }
            if ($act === 'events' && $subId && isset($segments[4]) && $segments[4] === 'publish' && $method === 'POST') { $admin->publishEvent($subId); return; }
            if ($act === 'questions' && $subId && isset($segments[4]) && $segments[4] === 'approve' && $method === 'POST') { $admin->approveQuestion($subId); return; }
            if ($act === 'questions' && $subId && isset($segments[4]) && $segments[4] === 'reject' && $method === 'POST') { $admin->rejectQuestion($subId); return; }
            if ($act === 'questions' && $subId && isset($segments[4]) && $segments[4] === 'replace' && $method === 'POST') { $admin->replaceQuestion($subId); return; }
            if ($act === 'settings') {
                if ($method === 'GET') { $admin->getSettings(); return; }
                if ($method === 'POST') { $admin->updateSettings(); return; }
            }
            notFound();
            return;
        }

        $ctrl = new SpecialEventController();

        if ($sub === 'today' && $method === 'GET') {
            $ctrl->today();
            return;
        }

        if ($sub === 'history' && $method === 'GET') {
            $ctrl->history();
            return;
        }

        // /special-event/bloom/history
        if ($sub === 'bloom' && isset($segments[2]) && $segments[2] === 'history' && $method === 'GET') {
            $ctrl->bloomHistory();
            return;
        }

        // /special-event/{id}/start
        if (is_numeric($sub) && isset($segments[2]) && $segments[2] === 'start' && $method === 'POST') {
            $ctrl->start((int)$sub);
            return;
        }

        // /special-event/attempts/{attemptId}/...
        if ($sub === 'attempts' && $id !== null) {
            $action = $segments[3] ?? '';
            if ($action === 'answer' && $method === 'POST') { $ctrl->submitAnswer($id); return; }
            if ($action === 'mark' && $method === 'POST') { $ctrl->toggleMark($id); return; }
            if ($action === 'submit' && $method === 'POST') { $ctrl->finish($id); return; }
            if ($action === 'review' && $method === 'GET') { $ctrl->review($id); return; }
            if ($action === 'bloom' && isset($segments[4]) && $segments[4] === 'domains' && $method === 'GET') { $ctrl->bloomDomains($id); return; }
            if ($action === 'bloom' && $method === 'GET') { $ctrl->bloom($id); return; }
            if (!$action && $method === 'GET') { $ctrl->attempt($id); return; }
        }

        notFound();
        return;
    }

    // ── ADVENTURE ─────────────────────────────────────────────
    if ($segments[0] === 'adventure') {
        $ctrl = new AdventureController();
        $sub  = $segments[1] ?? '';
        $id   = (isset($segments[2]) && is_numeric($segments[2])) ? (int)$segments[2] : null;

        if ($sub === 'chapters') {
            if ($id === null && $method === 'GET') { $ctrl->chapters(); return; }
            if ($id !== null) {
                // /adventure/chapters/{id}/boss/complete or complete-boss
                if (isset($segments[3]) && $segments[3] === 'boss' && isset($segments[4]) && $segments[4] === 'complete') {
                    if ($method === 'POST') { $ctrl->completeBoss($id); return; }
                }
                if (isset($segments[3]) && $segments[3] === 'complete-boss') {
                    if ($method === 'POST') { $ctrl->completeBoss($id); return; }
                }
                if (isset($segments[3]) && $segments[3] === 'boss' && !isset($segments[4])) {
                    if ($method === 'GET') { $ctrl->boss($id); return; }
                }
                // /adventure/chapters/{id}/parts/{num}
                if (isset($segments[3]) && $segments[3] === 'parts' && isset($segments[4])) {
                    if ($method === 'GET') { $ctrl->part($id, (int)$segments[4]); return; }
                }
                if ($method === 'GET') { $ctrl->chapter($id); return; }
            }
        }
        if ($sub === 'story-state' && $method === 'GET') {
            $ctrl->storyState();
            return;
        }

        if ($sub === 'black-archive') {
            if ($method === 'GET' && !isset($segments[2])) {
                $ctrl->blackArchive();
                return;
            }
            if (isset($segments[2]) && $segments[2] === 'complete-phase' && $method === 'POST') {
                $ctrl->completeBlackArchivePhase();
                return;
            }
        }

        if ($sub === 'parts' && $id !== null) {
            if (isset($segments[3]) && $segments[3] === 'submit') {
                if ($method === 'POST') { $ctrl->submitPart($id); return; }
            }
        }
        if ($sub === 'tutorial' && isset($segments[2]) && $segments[2] === 'complete') {
            if ($method === 'POST') { $ctrl->completeTutorial(); return; }
        }

        notFound();
        return;
    }

    // ── CHRONICLE ──────────────────────────────────────────────
    if ($segments[0] === 'chronicle') {
        $ctrl = new ChronicleController();
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($id && $method === 'DELETE') { $ctrl->delete($id); return; }
        match($method) {
            'GET'  => $ctrl->feed(),
            'POST' => $ctrl->post(),
            default => notFound(),
        };
        return;
    }

    // ── CURRICULUM (PSYCHOLOGY STUDY PATH) ─────────────────────
    if ($segments[0] === 'curriculum') {
        $ctrl = new CurriculumController();
        $sub = $segments[1] ?? '';
        $id  = (isset($segments[2]) && is_numeric($segments[2])) ? (int)$segments[2] : null;

        if ($sub === 'domains') {
            if ($id) {
                if ($method === 'GET') { $ctrl->domain($id); return; }
            } else {
                if ($method === 'GET') { $ctrl->domains(); return; }
            }
        }
        if ($sub === 'parts') {
            if ($id && isset($segments[3]) && $segments[3] === 'submit') {
                if ($method === 'POST') { $ctrl->submitPart($id); return; }
            }
            if ($id) {
                if ($method === 'GET') { $ctrl->part($id); return; }
            }
        }
        if ($sub === 'overview' && $method === 'GET') {
            $ctrl->overview();
            return;
        }
        if ($sub === 'today' && $method === 'GET') {
            $ctrl->today();
            return;
        }

        notFound();
        return;
    }

    // ── AUTH ──────────────────────────────────────────────────
    if ($segments[0] === 'auth') {
        $ctrl = new AuthController();
        match([$method, $segments[1] ?? '']) {
            ['POST', 'register'] => $ctrl->register(),
            ['POST', 'login']    => $ctrl->login(),
            ['POST', 'logout']   => $ctrl->logout(),
            ['GET',  'me']       => $ctrl->me(),
            default              => notFound(),
        };
        return;
    }

    // ── QUESTS ────────────────────────────────────────────────
    if ($segments[0] === 'quests') {
        $ctrl = new QuestController();
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($id && isset($segments[2]) && $segments[2] === 'complete') {
            if ($method === 'POST') { $ctrl->complete($id); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'subtasks' && isset($segments[3])) {
            if ($method === 'POST') { $ctrl->toggleSubtask($id, (int)$segments[3]); return; }
        }
        if (isset($segments[1]) && $segments[1] === 'today') {
            if ($method === 'GET') { $ctrl->today(); return; }
        }
        match([$method, $id]) {
            ['GET',    null] => $ctrl->index(),
            ['POST',   null] => $ctrl->store(),
            ['GET',    $id]  => $ctrl->show($id),
            ['PUT',    $id]  => $ctrl->update($id),
            ['DELETE', $id]  => $ctrl->destroy($id),
            default          => notFound(),
        };
        return;
    }

    // ── BOSSES ────────────────────────────────────────────────
    if ($segments[0] === 'bosses') {
        $ctrl = new BossController();
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if (isset($segments[1]) && $segments[1] === 'history') {
            if ($method === 'GET') { $ctrl->history(); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'start') {
            if ($method === 'POST') { $ctrl->start($id); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'victory') {
            if ($method === 'POST') { $ctrl->recordVictory($id); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'defeat') {
            if ($method === 'POST') { $ctrl->recordDefeat($id); return; }
        }

        match([$method, $id]) {
            ['GET', null] => $ctrl->index(),
            ['GET', $id]  => $ctrl->show($id),
            default       => notFound(),
        };
        return;
    }

    // ── BOSS ATTEMPTS ─────────────────────────────────────────
    if ($segments[0] === 'attempts') {
        $ctrl      = new BossController();
        $attemptId = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($attemptId && isset($segments[2]) && $segments[2] === 'answer') {
            if ($method === 'POST') { $ctrl->submitAnswer($attemptId); return; }
        }
        if ($attemptId) {
            if ($method === 'GET') { $ctrl->getAttempt($attemptId); return; }
        }
        notFound();
        return;
    }

    // ── QUESTIONS ─────────────────────────────────────────────
    if ($segments[0] === 'questions') {
        $ctrl = new QuestionController();
        $action = $segments[1] ?? '';
        $id = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($action === 'adaptive-profile' && $method === 'GET') { $ctrl->adaptiveProfile(); return; }
        if ($action === 'session-summary' && $method === 'GET') { $ctrl->sessionSummary(); return; }
        if ($action === 'categories' && $method === 'GET') { $ctrl->categories(); return; }
        if ($action === 'topics' && $method === 'GET') { $ctrl->topics(); return; }
        if ($action === 'practice' && $method === 'GET') { $ctrl->practice(); return; }
        if ($action === 'attempt' && $method === 'POST') { $ctrl->submitAttempt(); return; }
        if ($action === 'weak-areas' && $method === 'GET') { $ctrl->weakAreas(); return; }
        if ($action === 'analytics' && $method === 'GET') { $ctrl->analytics(); return; }
        if ($action === 'generate-ai' && $method === 'POST') { $ctrl->generateAI(); return; }

        if ($id && isset($segments[2]) && $segments[2] === 'review' && $method === 'POST') {
            $ctrl->review($id);
            return;
        }

        notFound();
        return;
    }

    // ── REWARDS ───────────────────────────────────────────────
    if ($segments[0] === 'rewards') {
        $ctrl = new RewardController();
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if (isset($segments[1]) && $segments[1] === 'inventory') {
            if ($method === 'GET') { $ctrl->inventory(); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'purchase') {
            if ($method === 'POST') { $ctrl->purchase($id); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'use') {
            if ($method === 'POST') { $ctrl->useItem($id); return; }
        }

        match([$method, $id]) {
            ['GET', null] => $ctrl->index(),
            default       => notFound(),
        };
        return;
    }

    // ── ACHIEVEMENTS ──────────────────────────────────────────
    if ($segments[0] === 'achievements') {
        $ctrl = new AchievementController();
        match($method) {
            'GET'   => $ctrl->index(),
            default => notFound(),
        };
        return;
    }

    // ── PROFILE ───────────────────────────────────────────────
    if ($segments[0] === 'profile') {
        $ctrl = new ProfileController();

        if (isset($segments[1]) && $segments[1] === 'stats') {
            if ($method === 'GET') { $ctrl->stats(); return; }
        }

        match($method) {
            'GET'  => $ctrl->show(),
            'PUT'  => $ctrl->update(),
            default => notFound(),
        };
        return;
    }

    // ── MULTIPLAYER ───────────────────────────────────────────
    if ($segments[0] === 'multiplayer') {
        $ctrl = new MultiplayerController();
        $sub  = $segments[1] ?? '';

        if ($sub === 'questions') {
            if (isset($segments[2]) && $segments[2] === 'categories') {
                if ($method === 'GET') { $ctrl->getCategories(); return; }
            }
            if ($method === 'GET') { $ctrl->getQuestions(); return; }
        }
        if ($sub === 'match') {
            if (isset($segments[2]) && $segments[2] === 'save') {
                if ($method === 'POST') { $ctrl->saveMatch(); return; }
            }
        }
        if ($sub === 'history') {
            if ($method === 'GET') { $ctrl->getHistory(); return; }
        }
        if ($sub === 'stats') {
            if ($method === 'GET') { $ctrl->getStats(); return; }
        }
        if ($sub === 'leaderboard') {
            if ($method === 'GET') { $ctrl->getLeaderboard(); return; }
        }
        if ($sub === 'my-rank') {
            if ($method === 'GET') { $ctrl->getMyRank(); return; }
        }
        notFound();
        return;
    }

    // ── SHOP ──────────────────────────────────────────────────
    if ($segments[0] === 'shop') {
        $ctrl = new ShopController();
        $sub  = $segments[1] ?? '';
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($sub === 'inventory') {
            if ($method === 'GET') { $ctrl->inventory(); return; }
        }
        if ($sub === 'equipped') {
            if ($method === 'GET') { $ctrl->equipped(); return; }
        }
        if ($sub === 'items' || ($method === 'GET' && !$sub)) {
            if ($method === 'GET') { $ctrl->index(); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'purchase') {
            if ($method === 'POST') { $ctrl->purchase($id); return; }
        }
        if ($id && isset($segments[2]) && $segments[2] === 'equip') {
            if ($method === 'POST') { $ctrl->equip($id); return; }
        }
        if ($sub === 'unequip' && isset($segments[2])) {
            if ($method === 'POST') { $ctrl->unequip($segments[2]); return; }
        }
        if ($method === 'GET') { $ctrl->index(); return; }
        notFound();
        return;
    }

    notFound();
}

function notFound(): void {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Route not found']);
}
