<?php
session_start();

// Ініціалізація або відновлення стану гри
if (!isset($_SESSION['board']) || !isset($_SESSION['game_started'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $_SESSION['game_mode'] = '';
    $_SESSION['difficulty'] = '';
    $_SESSION['player1_name'] = '';
    $_SESSION['player2_name'] = '';
    $_SESSION['game_started'] = false;
}

// Обробка форми налаштувань гри
if (isset($_POST['start'])) {
    $_SESSION['game_started'] = true;
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $_SESSION['game_mode'] = $_POST['game_mode'];
    $_SESSION['difficulty'] = $_POST['difficulty'] ?? 'easy';
    $_SESSION['player1_name'] = $_POST['player1_name'];
    $_SESSION['player2_name'] = $_POST['game_mode'] === 'friend' ? $_POST['player2_name'] : 'Комп\'ютер';
}

// Перезапуск гри (зберігає налаштування)
if (isset($_POST['restart'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $_SESSION['game_started'] = true;
}

// Повернення до меню
if (isset($_POST['menu'])) {
    // Скидаємо стан гри повністю
    $_SESSION['game_started'] = false;
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $winning_combination = [];
    $has_winner = false;
    $is_draw = false;
    $game_over = false;
} else {
    $winning_combination = [];
}

// Обробка ходу гравця
if (isset($_POST['cell']) && $_SESSION['game_started']) {
    $position = (int)$_POST['cell'];
    if ($_SESSION['board'][$position] === '') {
        $_SESSION['board'][$position] = $_SESSION['current_player'];
        
        if (!checkWin() && !checkDraw()) {
            $_SESSION['current_player'] = ($_SESSION['current_player'] === 'X') ? 'O' : 'X';
            
            // Хід комп'ютера
            if ($_SESSION['game_mode'] === 'computer' && $_SESSION['current_player'] === 'O') {
                makeComputerMove();
            }
        }
    }
}

// Перевірка на перемогу
function checkWin($testMode = false) {
    global $winning_combination;
    $patterns = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6]
    ];
    
    foreach ($patterns as $pattern) {
        if ($_SESSION['board'][$pattern[0]] !== '' &&
            $_SESSION['board'][$pattern[0]] === $_SESSION['board'][$pattern[1]] &&
            $_SESSION['board'][$pattern[1]] === $_SESSION['board'][$pattern[2]]) {
            if (!$testMode) {
                $winning_combination = $pattern;
            }
            return true;
        }
    }
    if (!$testMode) {
        $winning_combination = [];
    }
    return false;
}

// Перевірка на нічию
function checkDraw() {
    return !in_array('', $_SESSION['board']);
}

// Хід комп'ютера
function makeComputerMove() {
    $position = -1;
    
    switch ($_SESSION['difficulty']) {
        case 'hard':
            $position = getBestMove();
            break;
        case 'medium':
            $position = (rand(1, 100) <= 70) ? getBestMove() : getRandomMove();
            break;
        default: // easy
            $position = getRandomMove();
    }
    
    if ($position !== -1) {
        $_SESSION['board'][$position] = 'O';
        if (!checkWin()) {
            $_SESSION['current_player'] = 'X';
        }
    }
}

// Випадковий хід
function getRandomMove() {
    $empty_cells = [];
    for ($i = 0; $i < 9; $i++) {
        if ($_SESSION['board'][$i] === '') {
            $empty_cells[] = $i;
        }
    }
    return !empty($empty_cells) ? $empty_cells[array_rand($empty_cells)] : -1;
}

// Розумний хід
function getBestMove() {
    // Спроба виграти
    for ($i = 0; $i < 9; $i++) {
        if ($_SESSION['board'][$i] === '') {
            $_SESSION['board'][$i] = 'O';
            if (checkWin(true)) {  // Використовуємо тестовий режим
                $_SESSION['board'][$i] = '';
                return $i;
            }
            $_SESSION['board'][$i] = '';
        }
    }
    
    // Блокування перемоги гравця
    for ($i = 0; $i < 9; $i++) {
        if ($_SESSION['board'][$i] === '') {
            $_SESSION['board'][$i] = 'X';
            if (checkWin(true)) {  // Використовуємо тестовий режим
                $_SESSION['board'][$i] = '';
                return $i;
            }
            $_SESSION['board'][$i] = '';
        }
    }
    
    // Центр
    if ($_SESSION['board'][4] === '') {
        return 4;
    }
    
    // Кути
    $corners = [0, 2, 6, 8];
    foreach ($corners as $corner) {
        if ($_SESSION['board'][$corner] === '') {
            return $corner;
        }
    }
    
    return getRandomMove();
}

$has_winner = checkWin();
$is_draw = checkDraw();
$game_over = $has_winner || $is_draw;
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Хрестики-нулики</title>
    <style>
        :root {
            --primary: #A86D83;
            --secondary: #EFB2D6;
            --light: #FDECD4;
            --accent: #FCD38F;
            --dark: #EA9D00;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--light);
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            text-align: center;
        }
        
        .status {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary);
            font-weight: bold;
            min-height: 36px;
        }
        
        .board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 0 auto;
            max-width: min(80vh, 600px);
        }
        
        .cell {
            aspect-ratio: 1;
            border: none;
            background-color: var(--secondary);
            font-size: min(15vw, 120px);
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cell:not([disabled]):hover {
            background-color: var(--accent);
            transform: scale(1.05);
        }
        
        .cell.winning {
            background-color: var(--dark);
            color: white;
            animation: pulse 1s infinite;
        }
        
        .cell.dimmed {
            opacity: 0.5;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background-color: var(--light);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
        }
        
        .modal h2 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid var(--secondary);
            border-radius: 8px;
            font-size: 16px;
        }
        
        .button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .button:hover {
            background-color: var(--dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status">
            <?php if ($has_winner): ?>
                <?php echo $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name']; ?> переміг!
            <?php elseif ($is_draw): ?>
                Нічия!
            <?php elseif ($_SESSION['game_started']): ?>
                Хід гравця: <?php echo $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name']; ?>
            <?php endif; ?>
        </div>

        <div class="board">
            <?php for($i = 0; $i < 9; $i++): ?>
                <form method="post" style="display: contents;">
                    <button type="submit" 
                            name="cell" 
                            value="<?php echo $i; ?>" 
                            class="cell <?php echo in_array($i, $winning_combination) ? 'winning' : 
                                           (!empty($winning_combination) ? 'dimmed' : ''); ?>"
                            <?php echo !$_SESSION['game_started'] || $game_over || $_SESSION['board'][$i] !== '' ? 'disabled' : ''; ?>>
                        <?php echo $_SESSION['board'][$i]; ?>
                    </button>
                </form>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Модальне вікно налаштувань -->
    <?php if (!$_SESSION['game_started']): ?>
    <div class="modal">
        <div class="modal-content">
            <h2>Нова гра</h2>
            <form method="post">
                <select name="game_mode" id="game-mode" onchange="togglePlayer2Input(this.value)">
                    <option value="computer">Гра з комп'ютером</option>
                    <option value="friend">Гра з другом</option>
                </select>
                
                <div id="difficulty-section">
                    <select name="difficulty">
                        <option value="easy">Легкий рівень</option>
                        <option value="medium">Середній рівень</option>
                        <option value="hard">Складний рівень</option>
                    </select>
                </div>

                <input type="text" name="player1_name" placeholder="Ім'я першого гравця" required>
                <input type="text" name="player2_name" id="player2-input" placeholder="Ім'я другого гравця" style="display: none;">
                
                <button type="submit" name="start" class="button">Почати гру</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Модальне вікно результату -->
    <?php if ($game_over): ?>
    <div class="modal">
        <div class="modal-content">
            <h2>
                <?php if ($has_winner): ?>
                    <?php echo $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name']; ?> переміг!
                <?php else: ?>
                    Нічия!
                <?php endif; ?>
            </h2>
            <form method="post" style="display: inline;">
                <button type="submit" name="restart" class="button">Нова гра</button>
                <button type="submit" name="menu" class="button">Меню</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function togglePlayer2Input(mode) {
            const player2Input = document.getElementById('player2-input');
            const difficultySection = document.getElementById('difficulty-section');
            
            if (mode === 'friend') {
                player2Input.style.display = 'block';
                difficultySection.style.display = 'none';
            } else {
                player2Input.style.display = 'none';
                difficultySection.style.display = 'block';
            }
        }
    </script>
</body>
</html>