<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Set UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');

function fetchSCBData() {
    $url = "https://api.scb.se/OV0104/v1/doris/sv/ssd/START/PR/PR0101/PR0101A/KPI19Bas1980";
    $query = [
        "query" => [
            [
                "code" => "Produktgrupp",
                "selection" => [
                    "filter" => "item",
                    "values" => [
                        "1113", // Vetebröd
                        "1412", // Mjölk
                        "1229", // Nötkött
                        "1410", // Ägg
                        "1611", // Potatis
                        "1127", // Pasta
                        "1506", // Smör
                        "1614", // Gul lök
                        "1706", // Kaffe
                        "1815"  // Socker
                    ]
                ]
            ],
            [
                "code" => "ContentsCode",
                "selection" => [
                    "filter" => "item",
                    "values" => ["000002ZJ"]
                ]
            ],
            [
                "code" => "Tid",
                "selection" => [
                    "filter" => "item",
                    "values" => [
                        "2019M01", "2019M02", "2019M03", "2019M04", "2019M05", "2019M06",
                        "2019M07", "2019M08", "2019M09", "2019M10", "2019M11", "2019M12",
                        "2020M01", "2020M02", "2020M03", "2020M04", "2020M05", "2020M06",
                        "2020M07", "2020M08", "2020M09", "2020M10", "2020M11", "2020M12",
                        "2021M01", "2021M02", "2021M03", "2021M04", "2021M05", "2021M06",
                        "2021M07", "2021M08", "2021M09", "2021M10", "2021M11", "2021M12",
                        "2022M01", "2022M02", "2022M03", "2022M04", "2022M05", "2022M06",
                        "2022M07", "2022M08", "2022M09", "2022M10", "2022M11", "2022M12",
                        "2023M01", "2023M02", "2023M03", "2023M04", "2023M05", "2023M06",
                        "2023M07", "2023M08", "2023M09", "2023M10", "2023M11", "2023M12",
                        "2024M01", "2024M02", "2024M03", "2024M04", "2024M05", "2024M06",
                        "2024M07", "2024M08", "2024M09", "2024M10", "2024M11", "2024M12"
                    ]
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check for API errors
    if ($httpCode !== 200 || !$response) {
        error_log("SCB API request failed with HTTP code: $httpCode");
        return ["data" => []];
    }

    $rawData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return ["data" => []];
    }

    // Log raw API response for debugging
    error_log("Raw API response: " . json_encode($rawData));

    // Map Produktgrupp codes to names
    $productMap = [
        "1113" => "Vetebröd",
        "1412" => "Mjölk",
        "1229" => "Nötkött",
        "1410" => "Ägg",
        "1611" => "Potatis",
        "1127" => "Pasta",
        "1506" => "Smör",
        "1614" => "Gul lök",
        "1706" => "Kaffe",
        "1815" => "Socker"
    ];

    // Aggregate monthly data into yearly averages
    $aggregatedData = [];
    foreach ($rawData['data'] as $item) {
        $produktgrupp = $item['key'][0];
        $tid = $item['key'][1];
        $value = floatval($item['values'][0]);
        
        // Extract year from Tid (e.g., "2019M01" -> "2019") and ensure it's a string
        $year = strval(substr($tid, 0, 4));
        
        if (!isset($aggregatedData[$produktgrupp])) {
            $aggregatedData[$produktgrupp] = [];
        }
        if (!isset($aggregatedData[$produktgrupp][$year])) {
            $aggregatedData[$produktgrupp][$year] = ['sum' => 0, 'count' => 0];
        }
        
        $aggregatedData[$produktgrupp][$year]['sum'] += $value;
        $aggregatedData[$produktgrupp][$year]['count'] += 1;
    }

    // Convert to the expected format
    $formattedData = ["data" => []];
    foreach ($aggregatedData as $produktgrupp => $years) {
        foreach ($years as $year => $stats) {
            // Skip if no data points (prevents division by zero)
            if ($stats['count'] === 0) {
                continue;
            }
            $avgPrice = $stats['sum'] / $stats['count'];
            // Only include products in $productMap
            if (isset($productMap[$produktgrupp])) {
                $formattedData['data'][] = [
                    "key" => [$productMap[$produktgrupp], $year],
                    "values" => [number_format($avgPrice, 2)]
                ];
            }
        }
    }

    // Log formatted data for debugging
    error_log("Formatted data: " . json_encode($formattedData, JSON_UNESCAPED_UNICODE));

    return $formattedData;
}

$data = fetchSCBData();

// Get list of unique products for dropdowns
$productMap = [
    "1113" => "Vetebröd",
    "1412" => "Mjölk",
    "1229" => "Nötkött",
    "1410" => "Ägg",
    "1611" => "Potatis",
    "1127" => "Pasta",
    "1506" => "Smör",
    "1614" => "Gul lök",
    "1706" => "Kaffe",
    "1815" => "Socker"
];
$products = array_values($productMap);
sort($products); // Sort alphabetically for better usability
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
            <div class="mb-4 flex space-x-4">
                <div>
                    <label for="vara" class="mr-2">Välj vara:</label>
                    <select id="vara" class="p-2 border rounded w-64">
                        <option value="all">Alla</option>
                        <?php
                        foreach ($products as $product) {
                            echo "<option value=\"$product\">$product</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="year" class="mr-2">Välj år:</label>
                    <select id="year" class="p-2 border rounded w-32">
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                        <option value="2019">2019</option>
                    </select>
                </div>
            </div>
            <table id="priceTable" class="display w-full">
                <thead>
                    <tr>
                        <th>Vara</th>
                        <th>År</th>
                        <th>Prisindex</th>
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
                        if (isset($prevPrices[$vara]) && $prevPrices[$vara] != 0) {
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
            <div class="mb-4 flex space-x-4">
                <div>
                    <label for="chartVara" class="mr-2">Välj vara för diagram:</label>
                    <select id="chartVara" class="p-2 border rounded w-64">
                        <option value="all">Alla</option>
                        <?php
                        foreach ($products as $product) {
                            echo "<option value=\"$product\">$product</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="chartYear" class="mr-2">Välj år för prisökning:</label>
                    <select id="chartYear" class="p-2 border rounded w-32">
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-xl font-semibold mb-2">Prisutveckling (Linjediagram)</h3>
                    <canvas id="lineChart"></canvas>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-2">Prisökning (Stapeldiagram)</h3>
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
        const rawData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;
        const years = ["2019", "2020", "2021", "2022", "2023", "2024"];
        const varor = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE); ?>;

        // Validate data structure
        const data = rawData && rawData.data ? rawData : { data: [] };
        console.log("Raw data from PHP:", rawData);
        console.log("Processed.array data:", data);
        console.log("Products (varor):", varor);

        // Colors for each product (10 distinct colors)
        const productColors = {
            'Vetebröd': '#3B82F6',  // Blue
            'Mjölk': '#EF4444',     // Red
            'Nötkött': '#10B981',   // Green
            'Ägg': '#F59E0B',       // Yellow
            'Potatis': '#8B5CF6',    // Purple
            'Pasta': '#EC4899',     // Pink
            'Smör': '#6B7280',      // Gray
            'Gul lök': '#14B8A6',   // Teal
            'Kaffe': '#F97316',     // Orange
            'Socker': '#6366F1'     // Indigo
        };

        // Line Chart
        const lineChart = new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: years,
                datasets: varor.map(vara => {
                    const datasetData = years.map(year => {
                        const item = data.data.find(d => String(d.key[0]) === String(vara) && String(d.key[1]) === String(year));
                        const value = item ? parseFloat(item.values[0]) : 0;
                        console.log(`Line chart - ${vara} in ${year}:`, { item, value });
                        return value;
                    });
                    return {
                        label: vara,
                        data: datasetData,
                        borderColor: productColors[vara],
                        fill: false
                    };
                })
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        title: { display: true, text: 'Prisindex' },
                        beginAtZero: false,
                        suggestedMin: 100,
                        suggestedMax: 500
                    },
                    x: { title: { display: true, text: 'År' } }
                }
            }
        });

        // Bar Chart (Price increase for selected year)
        const barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: varor,
                datasets: [{
                    label: 'Prisökning 2024 (%)',
                    data: [],
                    backgroundColor: varor.map(vara => productColors[vara])
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        title: { display: true, text: 'Prisökning (%)' },
                        suggestedMin: -5,
                        suggestedMax: 5
                    },
                    x: { 
                        title: { display: true, text: 'Vara' },
                        ticks: { 
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Function to update bar chart based on selected year
        function updateBarChart(selectedYear) {
            const prevYear = String(parseInt(selectedYear) - 1);
            const priceIncreases = varor.map(vara => {
                const prev = data.data.find(d => String(d.key[0]) === String(vara) && String(d.key[1]) === prevYear);
                const curr = data.data.find(d => String(d.key[0]) === String(vara) && String(d.key[1]) === selectedYear);
                console.log(`Bar chart - ${vara}:`, { prev, curr });
                if (prev && curr) {
                    const prevPrice = parseFloat(prev.values[0]);
                    const currPrice = parseFloat(curr.values[0]);
                    return prevPrice != 0 ? ((currPrice - prevPrice) / prevPrice) * 100 : 0;
                }
                return 0;
            });

            // Update chart data and label
            barChart.data.datasets[0].data = priceIncreases;
            barChart.data.datasets[0].label = `Prisökning ${selectedYear} (%)`;
            barChart.update();
        }

        // DataTable and event listeners
        $(document).ready(function() {
            $('#priceTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                columnDefs: [{ orderable: true, targets: '_all' }],
                language: {
                    search: "Sök:",
                    lengthMenu: "Visa _MENU_ rader per sida",
                    info: "Visar _START_ till _END_ av _TOTAL_ rader",
                    paginate: {
                        previous: "Föregående",
                        next: "Nästa"
                    },
                    emptyTable: "Ingen data tillgänglig i tabellen",
                    infoEmpty: "Visar 0 till 0 av 0 rader",
                    infoFiltered: "(filtrerat från _MAX_ totala rader)",
                    zeroRecords: "Inga matchande rader hittades"
                }
            });

            // Filter table by vara and year
            $('#vara, #year').on('change', function() {
                const vara = $('#vara').val();
                const year = $('#year').val();
                let search = '';
                if (vara && vara !== 'all') search += vara;
                if (year) search += (search ? ' ' : '') + year;
                $('#priceTable').DataTable().search(search).draw();
            });

            // Filter line chart by vara
            $('#chartVara').on('change', function() {
                const selected = $(this).val();
                lineChart.data.datasets.forEach(dataset => {
                    dataset.hidden = selected !== 'all' && dataset.label !== selected;
                });
                lineChart.update();
            });

            // Update bar chart when year changes
            $('#chartYear').on('change', function() {
                const selectedYear = $(this).val();
                updateBarChart(selectedYear);
            });

            // Initial bar chart render for 2024
            updateBarChart('2024');
        });
    </script>
</body>
</html>