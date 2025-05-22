<?php

$mongoUri = getenv('MONGODB_URI') ?: die("Missing MONGODB_URI");
$manager = new MongoDB\Driver\Manager($mongoUri);

function addPerson(array $person): void
{
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite;

    $document = [
        'first_name' => $person['first_name'],
        'last_name' => $person['last_name'],
        'email' => $person['email'],
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Conditionally add 'birthdate' only if it was provided
    if (!empty($person['birthdate'])) {
        $document['birthdate'] = new MongoDB\BSON\UTCDateTime(strtotime($person['birthdate']) * 1000);
    }

    $bulk->insert($document);
    $manager->executeBulkWrite('test.people', $bulk);
}

function deletePerson(string $id): void
{
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($id)]);
    $manager->executeBulkWrite('test.people', $bulk);
}

function listPeople(): void
{
    global $manager;
    $query = new MongoDB\Driver\Query([]);
    $rows = $manager->executeQuery('test.people', $query);

    echo "<h3>📋 Lista osób:</h3><ul>";
    foreach ($rows as $doc) {
        $id = (string) $doc->_id;
        echo "<li>
            {$doc->first_name} {$doc->last_name} ({$doc->email}) 
            <a href=\"?action=delete&id={$id}\" onclick=\"return confirm('Na pewno usunąć?')\">🗑️ Usuń</a>
        </li>";
    }
    echo "</ul>";
}

// Obsługa usuwania
if ($_GET['action'] ?? '' === 'delete' && !empty($_GET['id'])) {
    deletePerson($_GET['id']);
    echo "<p style='color: red;'>🗑️ Osoba została usunięta.</p>";
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
    ];

    if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email'])) {
        addPerson($data);
        echo "<p style='color: green;'>✅ Osoba została dodana.</p>";
    } else {
        echo "<p style='color: red;'>❌ Wypełnij wszystkie pola.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj osobę</title>
</head>
<body>
    <h2>➕ Dodaj nową osobę</h2>
    <form method="POST">
        <label>Imię: <input type="text" name="first_name" required></label><br><br>
        <label>Nazwisko: <input type="text" name="last_name" required></label><br><br>
        <label>Email: <input type="email" name="email" required></label><br><br>
        <label>Data urodzenia: <input type="date" name="birthdate"></label><br><br>
        <button type="submit">Zapisz</button>
    </form>

    <?php listPeople(); ?>
</body>
</html>
