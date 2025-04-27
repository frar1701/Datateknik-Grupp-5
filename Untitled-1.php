<?php
function fetchSCBData() {
    $url = "https://api.scb.se/OV0104/v1/doris/sv/ssd/START/PR/PR0101/PR0101A/KPI";
    $query = [
        "query" => [
            [
                "code" => "Varugrupp",
                "selection" => [
                    "filter" => "item",
                    "values" => ["Mjölk", "Kött", "Bröd"] // Replace with actual Varugrupp codes
                ]
            ],
            [
                "code" => "Tid",
                "selection" => [
                    "filter" => "item",
                    "values" => ["2019", "2020", "2021", "2022", "2023", "2024"]
                ]
            ]
        ],
        "response" => [
            "format" => "json"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Mock response for demonstration
    $mockData = [
        "data" => [
            ["key" => ["Mjölk", "2019"], "values" => ["30.0"]],
            ["key" => ["Mjölk", "2020"], "values" => ["31.5"]],
            ["key" => ["Mjölk", "2021"], "values" => ["32.0"]],
            ["key" => ["Mjölk", "2022"], "values" => ["35.0"]],
            ["key" => ["Mjölk", "2023"], "values" => ["38.0"]],
            ["key" => ["Mjölk", "2024"], "values" => ["40.0"]],
            ["key" => ["Kött", "2019"], "values" => ["100.0"]],
            ["key" => ["Kött", "2020"], "values" => ["105.0"]],
            ["key" => ["Kött", "2021"], "values" => ["110.0"]],
            ["key" => ["Kött", "2022"], "values" => ["120.0"]],
            ["key" => ["Kött", "2023"], "values" => ["130.0"]],
            ["key" => ["Kött", "2024"], "values" => ["140.0"]],
            ["key" => ["Bröd", "2019"], "values" => ["25.0"]],
            ["key" => ["Bröd", "2020"], "values" => ["26.0"]],
            ["key" => ["Bröd", "2021"], "values" => ["27.0"]],
            ["key" => ["Bröd", "2022"], "values" => ["30.0"]],
            ["key" => ["Bröd", "2023"], "values" => ["32.0"]],
            ["key" => ["Bröd", "2024"], "values" => ["35.0"]]
        ]
    ];

    return $mockData; // Replace with: return json_decode($response, true);
}

// Optional: Function to save data to MySQL (uncomment to use)
/*
function saveToDatabase($data) {
    $conn = new mysqli("localhost", "username", "password", "food_prices");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    foreach ($data['data'] as $item) {
        $vara = $item['key'][0];
        $year = $item['key'][1];
        $price = $item['values'][0];
        $sql = "INSERT INTO prices (vara, year, price) VALUES ('$vara', '$year', '$price')";
        $conn->query($sql);
    }
    $conn->close();
}
*/

$data = fetchSCBData();
// saveToDatabase($data); // Uncomment to save to database
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livsmedelsprisernas utveckling</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Navigation -->
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between">
            <div class="text-lg font-bold">Livsmedelspriser</div>
            <div class="space-x-4">
                <a href="#start" class="hover:underline">STARTSIDA</a>
                <a href="#stats" class="hover:underline">STATISTIK</a>
                <a href="#charts" class="hover:underline">DIAGRAM</a>
                <a href="#contact" class="hover:underline">KONTAKT</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <!-- Startsida -->
        <section id="start" class="mb-12">
            <h1 class="text-3xl font-bold mb-4">Välkommen till Livsmedelsprisernas utveckling</h1>
            <p class="text-lg">Utforska hur priserna på livsmedel har förändrats över tid i Sverige. Välj varor och år för att se detaljerad statistik och visualiseringar.</p>
        </section>

        <!-- Statistik (Table) -->
        <section id="stats" class="mb-12">
            <h2 class="text-2xl font-bold mb-4">Statistik</h2>
            <div class="mb-4">
                <label for="vara" class="mr-2">Välj vara:</label>
                <select id="vara" class="p-2 border rounded">
                    <option value="all">Alla</option>
                    <option value="Mjölk">Mjölk</option>
                    <option value="Kött">Kött</option>
                    <option value="Bröd">Bröd</option>
                </select>
                <label for="year" class="ml-4 mr-2">Välj år:</label>
                <select id="year" class="p-2 border rounded">
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                    <option value="2021">2021</option>
                    <option value="2020">2020</option>
                    <option value="2019">2019</option>
                </select>
            </div>
            <table id="priceTable" class="display w-full">
                <thead>
                    <tr>
                        <th>Vara</th>
                        <th>År</th>
                        <th>Pris (SEK)</th>
                        <th>Prisökning (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $prevPrices = [];
                    foreach ($data['data'] as $item) {
                        $vara = $item['key'][0];
                        $year = $item['key'][1];
                        $price = floatval($item['values'][0]);
                        $priceIncrease = 0;
                        if (isset($prevPrices[$vara])) {
                            $prevPrice = $prevPrices[$vara];
                            $priceIncrease = (($price - $prevPrice) / $prevPrice) * 100;
                        }
                        $prevPrices[$vara] = $price;
                        echo "<tr><td>$vara</td><td>$year</td><td>$price</td><td>" . round($priceIncrease, 2) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>

        <!-- Diagram (Charts) -->
        <section id="charts" class="mb-12">
            <h2 class="text-2xl font-bold mb-4">Diagram</h2>
            <div class="mb-4">
                <label for="chartVara" class="mr-2">Välj vara för diagram:</label>
                <select id="chartVara" class="p-2 border rounded">
                    <option value="all">Alla</option>
                    <option value="Mjölk">Mjölk</option>
                    <option value="Kött">Kött</option>
                    <option value="Bröd">Bröd</option>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-2">Prisutveckling (Linjediagram)</h3>
                    <canvas id="lineChart"></canvas>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-2">Prisökning 2024 (Stapeldiagram)</h3>
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Kontakt -->
        <section id="contact">
            <h2 class="text-2xl font-bold mb-4">Kontakt</h2>
            <p class="text-lg">Kontakta oss för frågor eller feedback: <a href="mailto:info@livsmedelspriser.se" class="text-blue-600">info@livsmedelspriser.se</a></p>
        </section>
    </div>

    <!-- JavaScript for Charts and Table -->
    <script>
        // Data for charts
        const data = <?php echo json_encode($data); ?>;
        const years = ["2019", "2020", "2021", "2022", "2023", "2024"];
        const varor = ["Mjölk", "Kött", "Bröd"];

        // Line Chart
        const lineChart = new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: years,
                datasets: varor.map(vara => ({
                    label: vara,
                    data: years.map(year => {
                        const item = data.data.find(d => d.key[0] === vara && d.key[1] === year);
                        return item ? parseFloat(item.values[0]) : 0;
                    }),
                    borderColor: vara === 'Mjölk' ? '#3B82F6' : vara === 'Kött' ? '#EF4444' : '#10B981',
                    fill: false
                }))
            },
            options: {
                responsive: true,
                scales: {
                    y: { title: { display: true, text: 'Pris (SEK)' } },
                    x: { title: { display: true, text: 'År' } }
                }
            }
        });

        // Bar Chart (Price increase for 2024)
        const barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: varor,
                datasets: [{
                    label: 'Prisökning 2024 (%)',
                    data: varor.map(vara => {
                        const prev = data.data.find(d => d.key[0] === vara && d.key[1] === '2023');
                        const curr = data.data.find(d => d.key[0] === vara && d.key[1] === '2024');
                        if (prev && curr) {
                            const prevPrice = parseFloat(prev.values[0]);
                            const currPrice = parseFloat(curr.values[0]);
                            return ((currPrice - prevPrice) / prevPrice) * 100;
                        }
                        return 0;
                    }),
                    backgroundColor: ['#3B82F6', '#EF4444', '#10B981']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { title: { display: true, text: 'Prisökning (%)' } },
                    x: { title: { display: true, text: 'Vara' } }
                }
            }
        });

        // DataTable
        $(document).ready(function() {
            $('#priceTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                columnDefs: [{ orderable: true, targets: '_all' }]
            });

            // Filter table by vara and year
            $('#vara, #year').on('change', function() {
                const vara = $('#vara').val();
                const year = $('#year').val();
                let search = '';
                if (vara !== 'all') search += vara;
                if (year) search += (search ? ' ' : '') + year;
                $('#priceTable').DataTable().search(search).draw();
            });
        });

        // Filter line chart by vara
        $('#chartVara').on('change', function() {
            const selected = $(this).val();
            lineChart.data.datasets.forEach(dataset => {
                dataset.hidden = selected !== 'all' && dataset.label !== selected;
            });
            lineChart.update();
        });
    </script>
</body>
</html>
```