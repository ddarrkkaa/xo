<?php
session_start();

// Обробка закінчення часу
if (isset($_POST['timeout']) && $_SESSION['game_started']) {
    // Поточний гравець програє, передаємо хід опоненту
    $current_player = $_SESSION['current_player'];
    $winning_player = ($current_player === 'X') ? 'O' : 'X';
    $_SESSION['current_player'] = $winning_player;
    $has_winner = true;
    $game_over = true;
}

// Ініціалізація або відновлення стану гри
if (!isset($_SESSION['board']) || !isset($_SESSION['game_started'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $_SESSION['game_mode'] = '';
    $_SESSION['difficulty'] = '';
    $_SESSION['player1_name'] = '';
    $_SESSION['player2_name'] = '';
    $_SESSION['game_started'] = false;
    $_SESSION['symbol_x'] = '🌟'; // Початковий символ для X
    $_SESSION['symbol_o'] = '🌙'; // Початковий символ для O
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
    $_SESSION['symbol_x'] = $_POST['symbol_x'];
    $_SESSION['symbol_o'] = $_POST['symbol_o'];
}

// Перезапуск гри (зберігає налаштування)
if (isset($_POST['restart'])) {
    $_SESSION['board'] = array_fill(0, 9, '');
    $_SESSION['current_player'] = 'X';
    $_SESSION['game_started'] = true;
}

// Повернення до меню
if (isset($_POST['menu'])) {
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
            if (checkWin(true)) {
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
            if (checkWin(true)) {
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
    <title>Хрестики-нулики </title>
    <link rel="icon" type="image/png" href="./tic-tac-toe.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="status">
            <?php if ($has_winner): ?>
                <?php 
                    $winner_symbol = $_SESSION['current_player'] === 'X' ? $_SESSION['symbol_x'] : $_SESSION['symbol_o'];
                    echo $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name']; 
                ?> переміг!
            <?php elseif ($is_draw): ?>
                Нічия!
            <?php elseif ($_SESSION['game_started']): ?>
                Хід: <?php 
                    $current_symbol = $_SESSION['current_player'] === 'X' ? $_SESSION['symbol_x'] : $_SESSION['symbol_o'];
                    $current_name = $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name'];
                    echo "$current_name ($current_symbol)";
                ?>
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
        onclick="return submitMove(this);"
        <?php echo !$_SESSION['game_started'] || $game_over || $_SESSION['board'][$i] !== '' ? 'disabled' : ''; ?>>

                        <?php 
                            if ($_SESSION['board'][$i] === 'X') {
                                echo $_SESSION['symbol_x'];
                            } elseif ($_SESSION['board'][$i] === 'O') {
                                echo $_SESSION['symbol_o'];
                            }
                        ?>
                    </button>
                </form>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Модальне вікно налаштувань -->
    <?php if (!$_SESSION['game_started']): ?>
    <div class="modal">
        <div class="modal-content">
            <h2>Нова магічна гра ✨</h2>
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
                
                <!-- Додаємо вибір символів -->
                <select name="symbol_x" class="symbol-select">
                    <option value="🌟">🌟 Зірка</option>
                    <option value="🌞">🌞 Сонце</option>
                    <option value="🦁">🦁 Лев</option>
                    <option value="🌈">🌈 Веселка</option>
                    <option value="⭐">⭐ Зірочка</option>
                    <option value="🎮">🎮 Геймпад</option>
                    <option value="❌">❌ Хрестик</option>
                </select>

                <select name="symbol_o" class="symbol-select">
                    <option value="🌙">🌙 Місяць</option>
                    <option value="🌚">🌚 Темний місяць</option>
                    <option value="🐯">🐯 Тигр</option>
                    <option value="🌺">🌺 Квітка</option>
                    <option value="💫">💫 Зірки</option>
                    <option value="🎲">🎲 Кубик</option>
                    <option value="⭕">⭕ Нулик</option>
                </select>
                
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
                    <?php 
                        $winner_symbol = $_SESSION['current_player'] === 'X' ? $_SESSION['symbol_x'] : $_SESSION['symbol_o'];
                        echo $_SESSION['current_player'] === 'X' ? $_SESSION['player1_name'] : $_SESSION['player2_name']; 
                        echo " ($winner_symbol) переміг!";
                    ?>
                <?php else: ?>
                    Нічия! 🤝
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

    // Додаємо затримку для ходу комп'ютера
    async function submitMove(button) {
        const isComputerGame = <?php echo json_encode($_SESSION['game_mode'] === 'computer'); ?>;
        const isPlayerX = <?php echo json_encode($_SESSION['current_player'] === 'X'); ?>;
        
        // Скидаємо таймер при кожному ході
        if (timerId) clearInterval(timerId);
        startTimer();
        
        if (isComputerGame && isPlayerX) {
            // Блокуємо всі клітинки на час ходу комп'ютера
            const cells = document.querySelectorAll('.cell:not([disabled])');
            cells.forEach(cell => cell.disabled = true);
            
            // Відправляємо форму з затримкою для комп'ютера
            setTimeout(() => {
                button.form.submit();
            }, 500);
            return false;
        }
        
        button.form.submit();
        return false;
    }

    function togglePlayer2Input(mode) {
        const player2Input = document.getElementById('player2-input');
        const difficultySection = document.getElementById('difficulty-section');
        
        if (mode === 'friend') {
            player2Input.style.display = 'block';
            player2Input.required = true;
            difficultySection.style.display = 'none';
        } else {
            player2Input.style.display = 'none';
            player2Input.required = false;
            difficultySection.style.display = 'block';
        }
    }
</script>
</body>
</html>