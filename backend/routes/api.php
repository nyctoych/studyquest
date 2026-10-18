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
require_once __DIR__ . '/../controllers/CharacterController.php';
require_once __DIR__ . '/../controllers/VaultController.php';
require_once __DIR__ . '/../controllers/BattlefieldPreferenceController.php';
require_once __DIR__ . '/../controllers/HallOfFameController.php';
require_once __DIR__ . '/../controllers/DailyChestController.php';

function dispatch(string $method, string $path): void {
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    // ── CHARACTERS (SELECTION & ATTRIBUTE ALLOCATION) ─────────
    if (!empty($segments) && ($segments[0] === 'characters' || $segments[0] === 'character')) {
        $ctrl = new CharacterController();
        $sub = $segments[1] ?? '';

        if ($sub === '' || $sub === null) {
            if ($method === 'GET') { $ctrl->index(); return; }
            if ($method === 'POST') { $ctrl->select(); return; }
        }
        if ($sub === 'select' && $method === 'POST') {
            $ctrl->select();
            return;
        }
        if (($sub === 'current' || $sub === 'me') && $method === 'GET') {
            $ctrl->current();
            return;
        }
        if ($sub === 'attribute-allocations' || $sub === 'attributes') {
            if (isset($segments[2]) && $segments[2] === 'claim' && $method === 'POST') {
                $ctrl->claimAttributeAllocation();
                return;
            }
            if ($method === 'GET') {
                $ctrl->getAttributeAllocations();
                return;
            }
            if ($method === 'POST') {
                $ctrl->claimAttributeAllocation();
                return;
            }
        }
        if ($method === 'GET' && !empty($sub)) {
            $ctrl->show($sub);
            return;
        }

        notFound();
        return;
    }

    // ── ADVENTURE ─────────────────────────────────────────────
    if (!empty($segments) && $segments[0] === 'adventure') {
        $ctrl = new AdventureController();
        $sub = $segments[1] ?? '';
        $id = (isset($segments[2]) && is_numeric($segments[2])) ? (int)$segments[2] : null;

        if ($sub === 'health' && $method === 'GET') {
            $ctrl->health();
            return;
        }

        if ($sub === 'chapters') {
            if ($id === null && $method === 'GET') { $ctrl->chapters(); return; }
            if ($id !== null) {
                // /adventure/chapters/{id}/final-boss/father-revelation
                if (isset($segments[3]) && $segments[3] === 'final-boss' && isset($segments[4]) && $segments[4] === 'father-revelation') {
                    if ($method === 'POST') { $ctrl->completeFatherRevelation(); return; }
                }
                // /adventure/chapters/{id}/final-boss/complete
                if (isset($segments[3]) && $segments[3] === 'final-boss' && isset($segments[4]) && $segments[4] === 'complete') {
                    if ($method === 'POST') { $ctrl->completeFinalBoss(); return; }
                }
                // /adventure/chapters/{id}/final-boss
                if (isset($segments[3]) && $segments[3] === 'final-boss' && !isset($segments[4])) {
                    if ($method === 'GET') { $ctrl->finalBoss(); return; }
                }
                // /adventure/chapters/{id}/parts/{num}
                if (isset($segments[3]) && $segments[3] === 'parts' && isset($segments[4])) {
                    if ($method === 'GET') { $ctrl->part($id, (int)$segments[4]); return; }
                }
                // /adventure/chapters/{id}/boss/complete
                if (isset($segments[3]) && $segments[3] === 'boss' && isset($segments[4]) && $segments[4] === 'complete') {
                    if ($method === 'POST') { $ctrl->completeBoss($id); return; }
                }
                // /adventure/chapters/{id}/boss
                if (isset($segments[3]) && $segments[3] === 'boss') {
                    if ($method === 'GET') { $ctrl->boss($id); return; }
                }
                if ($method === 'GET') { $ctrl->chapter($id); return; }
            }
        }

        if ($sub === 'dodge-potion' || $sub === 'dodge_potion' || (($sub === 'dodge-potion' || $sub === 'dodge_potion') && isset($segments[2]) && ($segments[2] === 'status' || $segments[2] === 'charges'))) {
            if ($method === 'GET') {
                $ctrl->dodgePotionStatus();
                return;
            }
        }

        if ($sub === 'battle') {
            if (isset($segments[2]) && $segments[2] === 'start' && $method === 'POST') {
                $ctrl->startBattle();
                return;
            }
            if (isset($segments[2]) && $segments[2] === 'action' && $method === 'POST') {
                $ctrl->battleAction();
                return;
            }
            if (isset($segments[2]) && ($segments[2] === 'dodge-potion' || $segments[2] === 'dodge_potion') && $method === 'POST') {
                $ctrl->dodgePotion();
                return;
            }
            if (isset($segments[2]) && ($segments[2] === 'abandon' || $segments[2] === 'leave') && $method === 'POST') {
                $ctrl->abandonBattle();
                return;
            }
        }

        if ($sub === 'final-boss') {
            if (isset($segments[2]) && $segments[2] === 'father-revelation' && $method === 'POST') {
                $ctrl->completeFatherRevelation();
                return;
            }
            if (isset($segments[2]) && $segments[2] === 'story-stage' && $method === 'POST') {
                $ctrl->finalBossStoryStage();
                return;
            }
            if (isset($segments[2]) && $segments[2] === 'complete' && $method === 'POST') {
                $ctrl->completeFinalBoss();
                return;
            }
            if ($method === 'GET') {
                $ctrl->finalBoss();
                return;
            }
        }

        if ($sub === 'story-state' && $method === 'GET') {
            $ctrl->storyState();
            return;
        }

        if ($sub === 'black-archive') {
            if (isset($segments[2]) && $segments[2] === 'complete-phase' && $method === 'POST') {
                $ctrl->completeBlackArchivePhase();
                return;
            }
            if ($method === 'GET') {
                $ctrl->blackArchive();
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

        if ($sub === 'analytics' && $method === 'GET') {
            $ctrl->analytics();
            return;
        }

        if ($sub === 'audit' && $method === 'GET') {
            $ctrl->auditCoverage();
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

        // /special-event/{id}/overview
        if (is_numeric($sub) && isset($segments[2]) && $segments[2] === 'overview' && $method === 'GET') {
            $ctrl->overview((int)$sub);
            return;
        }

        // /special-event/attempts/{attemptId}/...
        if ($sub === 'attempts' && $id !== null) {
            $action = $segments[3] ?? '';
            if ($action === 'answer' && $method === 'POST') { $ctrl->submitAnswer($id); return; }
            if ($action === 'mark' && $method === 'POST') { $ctrl->toggleMark($id); return; }
            if ($action === 'submit' && $method === 'POST') { $ctrl->finish($id); return; }
            if ($action === 'disqualify' && $method === 'POST') { $ctrl->disqualify($id); return; }
            if ($action === 'tab-switch' && $method === 'POST') { $ctrl->tabSwitch($id); return; }
            if ($action === 'review' && $method === 'GET') { $ctrl->review($id); return; }
            if ($action === 'bloom' && isset($segments[4]) && $segments[4] === 'domains' && $method === 'GET') { $ctrl->bloomDomains($id); return; }
            if ($action === 'bloom' && $method === 'GET') { $ctrl->bloom($id); return; }
            if (!$action && $method === 'GET') { $ctrl->attempt($id); return; }
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
        $sub1 = $segments[1] ?? '';
        $sub2 = $segments[2] ?? '';

        if ($sub1 === 'password' && $sub2 === 'update' && $method === 'POST') {
            $ctrl->updatePassword();
            return;
        }

        match([$method, $sub1]) {
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

    // ── PROFILE ───────────────────────────────────────────────
    if ($segments[0] === 'profile') {
        $ctrl = new ProfileController();

        if (isset($segments[1]) && ($segments[1] === 'mindkeeper' || $segments[1] === 'public') && isset($segments[2]) && is_numeric($segments[2])) {
            if ($method === 'GET') { $ctrl->mindkeeper((int)$segments[2]); return; }
        }

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

    // ── VAULT (PERSONAL REVIEWER ARCHIVE) ─────────────────────
    if ($segments[0] === 'vault') {
        $ctrl = new VaultController();
        $sub  = $segments[1] ?? '';
        $id   = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;

        if ($sub === 'upload' && $method === 'POST') {
            $ctrl->upload();
            return;
        }

        if ($sub === 'user' && isset($segments[2]) && is_numeric($segments[2])) {
            if ($method === 'GET') { $ctrl->userVault((int)$segments[2]); return; }
        }

        if ($sub === 'view' && isset($segments[2]) && is_numeric($segments[2])) {
            if ($method === 'GET') { $ctrl->preview((int)$segments[2]); return; }
        }

        if ($id !== null) {
            $action = $segments[2] ?? '';
            if ($action === 'preview' && $method === 'GET') { $ctrl->preview($id); return; }
            if ($action === 'content' && $method === 'GET') { $ctrl->content($id); return; }
            if (($action === 'content' || $action === 'save') && ($method === 'PUT' || $method === 'POST')) { $ctrl->saveContent($id); return; }
            if ($action === 'download' && $method === 'GET') { $ctrl->download($id); return; }
            if ($method === 'GET') { $ctrl->show($id); return; }
            if ($method === 'PUT' || $method === 'POST') { $ctrl->update($id); return; }
            if ($method === 'DELETE') { $ctrl->destroy($id); return; }
        }

        if ($method === 'GET') {
            $ctrl->index();
            return;
        }
        if ($method === 'POST') {
            $ctrl->upload();
            return;
        }

        notFound();
        return;
    }

    // ── ACHIEVEMENTS ──────────────────────────────────────────
    if ($segments[0] === 'achievements') {
        $ctrl = new AchievementController();
        $sub  = $segments[1] ?? '';

        if ($sub === '' || $sub === null) {
            if ($method === 'GET') { $ctrl->index(); return; }
        }
        if ($sub === 'claim' && $method === 'POST') {
            $ctrl->claim();
            return;
        }
        if ($sub === 'claim-all' && $method === 'POST') {
            $ctrl->claimAll();
            return;
        }
        if ($sub === 'public' && isset($segments[2])) {
            if ($method === 'GET') { $ctrl->publicShare((string)$segments[2]); return; }
        }

        notFound();
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
        if ($sub === 'combat-profile') {
            if ($method === 'GET') { $ctrl->getCombatProfile(); return; }
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

    // ── BATTLEFIELD PREFERENCES ───────────────────────────────
    if (!empty($segments) && $segments[0] === 'battlefield') {
        $ctrl = new BattlefieldPreferenceController();
        $sub = $segments[1] ?? '';
        if ($sub === 'preferences') {
            if ($method === 'GET') { $ctrl->getPreferences(); return; }
            if ($method === 'POST') { $ctrl->updatePreferences(); return; }
        }
        notFound();
        return;
    }

    // ── HALL OF FAME ──────────────────────────────────────────
    if (!empty($segments) && ($segments[0] === 'hall-of-fame' || $segments[0] === 'halloffame')) {
        $ctrl = new HallOfFameController();
        $sub = $segments[1] ?? '';

        if ($sub === 'announcements' && $method === 'GET') {
            $ctrl->announcements();
            return;
        }
        if (($sub === 'latest-passers' || $sub === 'latest') && $method === 'GET') {
            $ctrl->latestPassers();
            return;
        }
        if ($sub === 'batches' && $method === 'GET') {
            $ctrl->batches();
            return;
        }
        if (($sub === 'my-claims' || $sub === 'my-claim') && $method === 'GET') {
            $ctrl->myClaims();
            return;
        }
        if ($sub === 'claim' && $method === 'POST') {
            $ctrl->submitClaim();
            return;
        }
        if ($sub === 'admin') {
            $adminSub = $segments[2] ?? '';
            if ($adminSub === 'queue' && $method === 'GET') {
                $ctrl->adminQueue();
                return;
            }
            if ($adminSub === 'decide' && $method === 'POST') {
                $ctrl->adminDecide();
                return;
            }
        }
        notFound();
        return;
    }

    // ── TUTORIAL ONBOARDING & PROGRESS ────────────────────────
    if (!empty($segments) && $segments[0] === 'tutorial') {
        require_once __DIR__ . '/../controllers/TutorialController.php';
        $ctrl = new TutorialController();
        $sub = $segments[1] ?? '';

        if ($sub === 'status' && $method === 'GET') {
            $ctrl->getStatus();
            return;
        }
        if ($sub === 'progress' && $method === 'POST') {
            $ctrl->saveProgress();
            return;
        }
        if ($sub === 'complete' && $method === 'POST') {
            $ctrl->complete();
            return;
        }
        if ($sub === 'skip' && $method === 'POST') {
            $ctrl->skip();
            return;
        }
        if (($sub === 'reset' || $sub === 'replay') && $method === 'POST') {
            $ctrl->reset();
            return;
        }
        notFound();
        return;
    }

    // ── DAILY CHEST ───────────────────────────────────────────
    if (!empty($segments) && ($segments[0] === 'daily-chest' || $segments[0] === 'daily_chest')) {
        $ctrl = new DailyChestController();
        $sub = $segments[1] ?? '';
        if (($sub === '' || $sub === 'status') && $method === 'GET') { $ctrl->getStatus(); return; }
        if ($sub === 'claim' && $method === 'POST') { $ctrl->claim(); return; }
        notFound();
        return;
    }

    // ── DIRECT BATTLE & DODGE POTION ROOT FALLBACKS ───────────
    if (!empty($segments) && $segments[0] === 'battle') {
        $ctrl = new AdventureController();
        $action = $segments[1] ?? '';
        if ($action === 'start' && $method === 'POST') { $ctrl->startBattle(); return; }
        if ($action === 'action' && $method === 'POST') { $ctrl->battleAction(); return; }
        if (($action === 'dodge-potion' || $action === 'dodge_potion') && $method === 'POST') { $ctrl->dodgePotion(); return; }
        if (($action === 'abandon' || $action === 'leave') && $method === 'POST') { $ctrl->abandonBattle(); return; }
    }

    if (!empty($segments) && ($segments[0] === 'dodge-potion' || $segments[0] === 'dodge_potion')) {
        $ctrl = new AdventureController();
        if ($method === 'GET') { $ctrl->dodgePotionStatus(); return; }
        if ($method === 'POST') { $ctrl->dodgePotion(); return; }
    }

    notFound();
}

function notFound(): void {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Route not found']);
}
