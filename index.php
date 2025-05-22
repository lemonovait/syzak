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

    if (!empty($person['gender'])) {
        $document['gender'] = $person['gender'];
    }

    if (!empty($person['province'])) {
        $document['province'] = $person['province'];
    }

    if (!empty($person['city'])) {
        $document['city'] = $person['city'];
    }

    if (!empty($person['appliances'])) {
        $document['appliances'] = $person['appliances']; // array of strings
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

    echo "<h3>📋 Lista osób:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<thead>
        <tr>
            <th>Imię</th>
            <th>Nazwisko</th>
            <th>Email</th>
            <th>Płeć</th>
            <th>Województwo</th>
            <th>Miasto</th>
            <th>Sprzęt AGD</th>
            <th>Akcje</th>
        </tr>
    </thead><tbody>";

    foreach ($rows as $doc) {
        $id = (string) $doc->_id;
        $gender = $doc->gender ?? '-';
        $province = $doc->province ?? '-';
        $city = $doc->city ?? '-';
        $appliances = isset($doc->appliances) && is_array($doc->appliances)
            ? implode(', ', $doc->appliances)
            : '-';

        echo "<tr>
            <td>{$doc->first_name}</td>
            <td>{$doc->last_name}</td>
            <td>{$doc->email}</td>
            <td>{$gender}</td>
            <td>{$province}</td>
            <td>{$city}</td>
            <td>{$appliances}</td>
            <td><a href=\"?action=delete&id={$id}\" onclick=\"return confirm('Na pewno usunąć?')\">🗑️ Usuń</a></td>
        </tr>";
    }

    echo "</tbody></table>";
}

// Obsługa usuwania
if ($_GET['action'] ?? '' === 'delete' && !empty($_GET['id'])) {
    deletePerson($_GET['id']);
    header('Location: /?message=' . urlencode('🗑️ Osoba została usunięta.'), true, 303);
    exit;
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'province' => $_POST['province'] ?? '',
        'city' => $_POST['city'] ?? '',
        'appliances' => $_POST['appliances'] ?? []
    ];

    if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email'])) {
        addPerson($data);
        header('Location: /?message=' . urlencode('✅ Osoba została dodana.'), true, 303);
    } else {
        echo "<p style='color: red;'>❌ Wypełnij wszystkie pola obowiązkowe.</p>";
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

    <label>Płeć:
        <select name="gender">
            <option value="">-- wybierz --</option>
            <option value="Mężczyzna">Mężczyzna</option>
            <option value="Kobieta">Kobieta</option>
            <option value="Inne">Inne</option>
        </select>
    </label><br><br>

    <label>Województwo:
        <select name="province">
            <option value="">-- wybierz --</option>
            <option value="Dolnośląskie">Dolnośląskie</option>
            <option value="Kujawsko-pomorskie">Kujawsko-pomorskie</option>
            <option value="Lubelskie">Lubelskie</option>
            <option value="Lubuskie">Lubuskie</option>
            <option value="Łódzkie">Łódzkie</option>
            <option value="Małopolskie">Małopolskie</option>
            <option value="Mazowieckie">Mazowieckie</option>
            <option value="Opolskie">Opolskie</option>
            <option value="Podkarpackie">Podkarpackie</option>
            <option value="Podlaskie">Podlaskie</option>
            <option value="Pomorskie">Pomorskie</option>
            <option value="Śląskie">Śląskie</option>
            <option value="Świętokrzyskie">Świętokrzyskie</option>
            <option value="Warmińsko-mazurskie">Warmińsko-mazurskie</option>
            <option value="Wielkopolskie">Wielkopolskie</option>
            <option value="Zachodniopomorskie">Zachodniopomorskie</option>
        </select>
    </label><br><br>

    <label>Miasto: <input type="text" name="city"></label><br><br>

    <fieldset>
        <legend>Sprzęt AGD:</legend>
        <label><input type="checkbox" name="appliances[]" value="Pralka"> Pralka</label><br>
        <label><input type="checkbox" name="appliances[]" value="Telewizor"> Telewizor</label><br>
        <label><input type="checkbox" name="appliances[]" value="Piekarnik"> Piekarnik</label><br>
    </fieldset><br>

    <button type="submit">Zapisz</button>
    </form>

    <?php
    if (!empty($_GET['message'])) {
        echo '<p style="color: green;">' . htmlspecialchars($_GET['message']) . '</p>';
    }
    ?>
    
    <?php listPeople(); ?>
</body>
</html>
