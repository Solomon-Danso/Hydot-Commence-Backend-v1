<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Expenses;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\OurPortfolioProjects;
use App\Models\AuditTrial;
use Illuminate\Support\Facades\Log;
use App\Models\Visitors;
use App\Models\Order;
use App\Models\ProductAssessment;
use App\Models\Product;
use App\Models\CustomerTrail;
use App\Models\RateLimitCatcher;
use App\Models\Delivery;


class DashBoard extends Controller
{


    function ViewTotalSales() {
        $totalSales = Payment::sum('AmountPaid');
        return response()->json(['payments' => $totalSales]);
    }

    function ViewTotalExpenses() {
        $totalExpenses = Expenses::sum('AmountPaid');
        return response()->json(['expenses' => $totalExpenses]);
    }

    function ViewTotalYearlySales() {
        $currentYear = Carbon::now()->year;
        $salesData = [];
        $totalSalesOver5Years = 0;

        // Step 1: Calculate total sales for each year and the total sales over 5 years
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $totalSales = Payment::whereYear('updated_at', $year)->sum('AmountPaid');
            $salesData[] = [
                'year' => $year,
                'amount' => $totalSales
            ];
            $totalSalesOver5Years += $totalSales;
        }

        // Step 2: Calculate the percentage for each year's sales
        foreach ($salesData as &$data) {
            $data['percentage'] = $totalSalesOver5Years > 0 ? ($data['amount'] / $totalSalesOver5Years) * 100 : 0;
        }

        return response()->json(['payments' => $salesData]);
    }


    function ViewMonthlySalesAndExpenses() {
        $currentYear = Carbon::now()->year;
        $monthlySales = DB::table('payments')
            ->select(DB::raw('MONTH(updated_at) as month'), DB::raw('SUM(Amount) as total_sales'))
            ->whereYear('updated_at', $currentYear)
            ->groupBy(DB::raw('MONTH(updated_at)'))
            ->get()
            ->keyBy('month')
            ->toArray();

        $monthlyExpenses = DB::table('expenses')
            ->select(DB::raw('MONTH(updated_at) as month'), DB::raw('SUM(Amount) as total_expenses'))
            ->whereYear('updated_at', $currentYear)
            ->groupBy(DB::raw('MONTH(updated_at)'))
            ->get()
            ->keyBy('month')
            ->toArray();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $stackedChartData = [[], []];

        for ($i = 1; $i <= 12; $i++) {
            $sales = isset($monthlySales[$i]) ? $monthlySales[$i]->total_sales : 0;
            $expenses = isset($monthlyExpenses[$i]) ? $monthlyExpenses[$i]->total_expenses : 0;

            $stackedChartData[0][] = ['x' => $months[$i - 1], 'y' => (float)$sales];
            $stackedChartData[1][] = ['x' => $months[$i - 1], 'y' => (float)$expenses];
        }

        $stackedCustomSeries = [
            [
                'dataSource' => $stackedChartData[0],
                'xName' => 'x',
                'yName' => 'y',
                'name' => 'Earnings',
                'type' => 'StackingColumn',
                'background' => 'blue',
            ],
            [
                'dataSource' => $stackedChartData[1],
                'xName' => 'x',
                'yName' => 'y',
                'name' => 'Expense',
                'type' => 'StackingColumn',
                'background' => 'red',
            ],
        ];

        return response()->json([
            'stackedCustomSeries' => $stackedCustomSeries
        ]);
    }


function ViewTotalSalesForCurrentMonth() {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    // Get total sales for the current month
    $totalSales = DB::table('payments')
        ->whereYear('updated_at', $currentYear)
        ->whereMonth('updated_at', $currentMonth)
        ->sum('AmountPaid');

    // Detect database type
    $databaseConnection = config('database.default');
    $databaseDriver = config("database.connections.$databaseConnection.driver");

    // Define the first day of the month
    $firstDayOfMonth = Carbon::now()->startOfMonth()->toDateString();

    // Build the query for weekly sales
    if ($databaseDriver == 'mysql') {
        $weeklySales = DB::table('payments')
            ->select(DB::raw("
                WEEK(updated_at, 1) - WEEK('$firstDayOfMonth', 1) + 1 as week,
                SUM(AmountPaid) as total_sales
            "))
            ->whereYear('updated_at', $currentYear)
            ->whereMonth('updated_at', $currentMonth)
            ->groupBy(DB::raw("WEEK(updated_at, 1) - WEEK('$firstDayOfMonth', 1) + 1"))
            ->get()
            ->keyBy('week')
            ->toArray();
    } else if ($databaseDriver == 'sqlsrv') {
        $weeklySales = DB::table('payments')
            ->select(DB::raw("
                DATEPART(WEEK, updated_at) - DATEPART(WEEK, '$firstDayOfMonth') + 1 as week,
                SUM(AmountPaid) as total_sales
            "))
            ->whereYear('updated_at', $currentYear)
            ->whereMonth('updated_at', $currentMonth)
            ->groupBy(DB::raw("DATEPART(WEEK, updated_at) - DATEPART(WEEK, '$firstDayOfMonth') + 1"))
            ->get()
            ->keyBy('week')
            ->toArray();
    }

    $SparklineAreaData = [];
    for ($week = 1; $week <= 5; $week++) {
        $sales = isset($weeklySales[$week]) ? $weeklySales[$week]->total_sales : 0;
        $SparklineAreaData[] = ['x' => $week, 'yval' => (float)$sales];
    }

    return response()->json([
        'SparklineAreaData' => $SparklineAreaData,
        'totalSales' => (float)$totalSales
    ]);
}





function ThisYearSales() {
    $currentYear = Carbon::now()->year;
    $currentYearSales = Payment::whereYear('updated_at', $currentYear)->sum('AmountPaid');

    return response()->json(['thisYearSales' => $currentYearSales ]);
}

function TotalCustomers(){
    $c = Customer::Count();
    return response()->json(["customers"=>$c],200);
}

function EarningData() {
    $currentYear = Carbon::now()->year;
    $previousYear = $currentYear - 1;

    // 1. Count total customers from Customer table
    $totalCustomersCurrentYear = Customer::whereYear('created_at', $currentYear)->count();
    $totalCustomersPreviousYear = Customer::whereYear('created_at', $previousYear)->count();

    // 2. Count total products from Product table
    $totalProductsCurrentYear = Product::whereYear('created_at', $currentYear)->count();
    $totalProductsPreviousYear = Product::whereYear('created_at', $previousYear)->count();

    // 3. Sum current year sales
    $currentYearSales = Payment::whereYear('updated_at', $currentYear)->sum('AmountPaid');

    // 4. Sum previous year sales
    $previousYearSales = Payment::whereYear('updated_at', $previousYear)->sum('AmountPaid');

    // 5. Count total deliveries from Delivery table
    $totalDeliveriesCurrentYear = Delivery::whereYear('created_at', $currentYear)->count();
    $totalDeliveriesPreviousYear = Delivery::whereYear('created_at', $previousYear)->count();

    // Calculate percentage changes
    $customersPercentageChange = $totalCustomersPreviousYear > 0
        ? (($totalCustomersCurrentYear - $totalCustomersPreviousYear) / $totalCustomersPreviousYear) * 100
        : 100;

    $productsPercentageChange = $totalProductsPreviousYear > 0
        ? (($totalProductsCurrentYear - $totalProductsPreviousYear) / $totalProductsPreviousYear) * 100
        : 100;

    $salesPercentageChange = $previousYearSales > 0
        ? (($currentYearSales - $previousYearSales) / $previousYearSales) * 100
        : 100;

    $deliveriesPercentageChange = $totalDeliveriesPreviousYear > 0
        ? (($totalDeliveriesCurrentYear - $totalDeliveriesPreviousYear) / $totalDeliveriesPreviousYear) * 100
        : 100;

    // Determine increase or decrease
    $customersPercentage = ($customersPercentageChange >= 0 ? '+' : '') . number_format($customersPercentageChange, 2) . '%';
    $customersColor = $customersPercentageChange >= 0 ? 'green-600' : 'red-600';

    $productsPercentage = ($productsPercentageChange >= 0 ? '+' : '') . number_format($productsPercentageChange, 2) . '%';
    $productsColor = $productsPercentageChange >= 0 ? 'green-600' : 'red-600';

    $salesPercentage = ($salesPercentageChange >= 0 ? '+' : '') . number_format($salesPercentageChange, 2) . '%';
    $salesColor = $salesPercentageChange >= 0 ? 'green-600' : 'red-600';

    $deliveriesPercentage = ($deliveriesPercentageChange >= 0 ? '+' : '') . number_format($deliveriesPercentageChange, 2) . '%';
    $deliveriesColor = $deliveriesPercentageChange >= 0 ? 'green-600' : 'red-600';

    // Formatting amounts
    $formattedCurrentYearSales = number_format($currentYearSales, 2);

    $earningData = [
        [
            'icon' => 'MdOutlineSupervisorAccount',
            'amount' => number_format($totalCustomersCurrentYear),
            'percentage' => $customersPercentage,
            'title' => 'Customers',
            'iconColor' => '#03C9D7',
            'iconBg' => '#E5FAFB',
            'pcColor' => $customersColor,
        ],
        [
            'icon' => 'BsBoxSeam',
            'amount' => number_format($totalProductsCurrentYear),
            'percentage' => $productsPercentage,
            'title' => 'Products',
            'iconColor' => 'rgb(255, 244, 229)',
            'iconBg' => 'rgb(254, 201, 15)',
            'pcColor' => $productsColor,
        ],
        [
            'icon' => 'FiBarChart',
            'amount' => '₵' . $formattedCurrentYearSales,
            'percentage' => $salesPercentage,
            'title' => 'Sales',
            'iconColor' => 'rgb(228, 106, 118)',
            'iconBg' => 'rgb(255, 244, 229)',
            'pcColor' => $salesColor,
        ],
        [
            'icon' => 'MdLocalShipping',
            'amount' => number_format($totalDeliveriesCurrentYear),
            'percentage' => $deliveriesPercentage,
            'title' => 'Deliveries',
            'iconColor' => 'rgb(0, 194, 146)',
            'iconBg' => 'rgb(235, 250, 242)',
            'pcColor' => $deliveriesColor,
        ],
    ];

    return response()->json(['earningData' => $earningData]);
}


// function RecentTransaction() {
//     // Fetch recent sales data
//     $salesData = Payment::select('updated_at as date', 'AmountPaid as amount', 'ReferenceId as title')
//         ->orderBy('updated_at', 'desc')
//         ->limit(5)
//         ->get()
//         ->toArray();

//     // Fetch recent expenses data
//     $expensesData = Expenses::select('updated_at as date', 'AmountPaid as amount')
//         ->orderBy('updated_at', 'desc')
//         ->limit(5)
//         ->get()
//         ->toArray();

//     // Combine and sort data
//     $combinedData = array_merge(
//         array_map(function ($item) {
//             return [
//                 'date' => $item['date'],
//                 'amount' => $item['amount'],
//                 'title' => $item['title'],
//                 'desc' => 'Money Added',
//                 'type' => 'payments'
//             ];
//         }, $salesData),
//         array_map(function ($item) {
//             return [
//                 'date' => $item['date'],
//                 'amount' => $item['amount'],
//                 'title' => 'Expenses',
//                 'desc' => 'Bill Payment',
//                 'type' => 'expenses'
//             ];
//         }, $expensesData)
//     );

//     // Sort combined data by date
//     usort($combinedData, function ($a, $b) {
//         return strtotime($b['date']) - strtotime($a['date']);
//     });

//     // Pick the five most recent transactions
//     $recentTransactions = array_slice($combinedData, 0, 5);

//     // Format the data as required
//     $formattedTransactions = array_map(function ($item) {
//         $isPositive = $item['type'] === 'payments';
//         return [
//             'icon' => $isPositive ? 'BsCurrencyDollar' : 'BsShield',
//             'amount' => ($isPositive ? '+' : '-') . '₵' . number_format($item['amount'], 2),
//             'title' => $item['title'],
//             'desc' => $item['desc'],
//             'iconColor' => $isPositive ? '#03C9D7' : 'rgb(0, 194, 146)',
//             'iconBg' => $isPositive ? '#E5FAFB' : 'rgb(235, 250, 242)',
//             'pcColor' => $isPositive ? 'green-600' : 'red-600',
//         ];
//     }, $recentTransactions);

//     return response()->json(['recentTransactions' => $formattedTransactions]);
// }


function RecentTransaction() {
    // Fetch recent sales data from the Payment table
    $salesData = Payment::select('updated_at as date', 'AmountPaid as amount', 'OrderId as title')
        ->orderBy('updated_at', 'desc')
        ->limit(5)
        ->get()
        ->toArray();

    // Format the data as required
    $formattedTransactions = array_map(function ($item) {
        return [
            'icon' => 'BsCurrencyDollar',
            'amount' => '+₵' . number_format($item['amount'], 2),
            'title' => "OrderId: ".$item['title'],
            'desc' => 'Money Added',
            'iconColor' => '#03C9D7',
            'iconBg' => '#E5FAFB',
            'pcColor' => 'green-600',
        ];
    }, $salesData);

    return response()->json(['recentTransactions' => $formattedTransactions]);
}




// function YearlyContinent() {
//     // Fetch yearly sales data
//     $yearlySales = Payment::selectRaw('DATEPART(YEAR, updated_at) as year, SUM(AmountPaid) as total_sales')
//         ->groupByRaw('DATEPART(YEAR, updated_at)')
//         ->orderByRaw('DATEPART(YEAR, updated_at) ASC')
//         ->get()
//         ->toArray();

//     Log::info("Main Yearly Payment");
//     Log::info($yearlySales);

//     $salesByContinent = [];

//     // Iterate over yearly sales data
//     foreach ($yearlySales as $yearlySale) {
//         $year = $yearlySale['year'];
//         $totalSales = intval($yearlySale['total_sales']); // Cast to integer

//         // Fetch sales records for the current year
//         $salesData = Payment::whereYear('updated_at', $year)->get();

//         // Iterate over sales records to retrieve continent information
//         foreach ($salesData as $sale) {
//             // Find the customer associated with the sale
//             $customer = Customer::where("UserId", $sale->CustomerId)->first();

//             // If customer found, extract continent information
//             if ($customer) {
//                 $continent = $customer->Continent;

//                 // Aggregate sales by continent for the current year
//                 if (!isset($salesByContinent[$continent])) {
//                     $salesByContinent[$continent] = [];
//                 }

//                 // Store total sales for the current year and continent
//                 $salesByContinent[$continent][] = ['x' => mktime(0, 0, 0, 1, 1, $year), 'y' => $totalSales];
//             }
//         }
//     }

//     // Format the data as required
//     $formattedData = [];

//     foreach ($salesByContinent as $continent => $sales) {
//         $formattedData[] = [
//             'dataSource' => $sales, // Use sales data directly
//             'xName' => 'x',
//             'yName' => 'y',
//             'name' => $continent,
//             'width' => '2',
//             'marker' => ['visible' => true, 'width' => 10, 'height' => 10],
//             'type' => 'Line'
//         ];
//     }

//     return response()->json($formattedData);
// }

function YearlyContinent() {
    // Fetch yearly sales data
    $yearlySales = Payment::selectRaw('YEAR(updated_at) as year, SUM(AmountPaid) as total_sales')
        ->groupByRaw('YEAR(updated_at)')
        ->orderByRaw('YEAR(updated_at) ASC')
        ->get()
        ->toArray();

    Log::info("Main Yearly Payment");
    Log::info($yearlySales);

    $salesByContinent = [];

    // Iterate over yearly sales data
    foreach ($yearlySales as $yearlySale) {
        $year = $yearlySale['year'];
        $totalSales = intval($yearlySale['total_sales']); // Cast to integer

        // Fetch sales records for the current year
        $salesData = Payment::whereYear('updated_at', $year)->get();

        // Iterate over sales records to retrieve continent information
        foreach ($salesData as $sale) {
            // Find the customer associated with the sale via OrderId
            $order = Order::where('OrderId', $sale->OrderId)->first();
            $customer = $order ? Customer::where('UserId', $order->UserId)->first() : null;

            // If customer found, extract continent information
            if ($customer) {
                $continent = $customer->Continent;

                // Aggregate sales by continent for the current year
                if (!isset($salesByContinent[$continent])) {
                    $salesByContinent[$continent] = [];
                }

                // Store total sales for the current year and continent
                $salesByContinent[$continent][] = ['x' => mktime(0, 0, 0, 1, 1, $year), 'y' => $totalSales];
            }
        }
    }

    // Format the data as required
    $formattedData = [];

    foreach ($salesByContinent as $continent => $sales) {
        $formattedData[] = [
            'dataSource' => $sales, // Use sales data directly
            'xName' => 'x',
            'yName' => 'y',
            'name' => $continent,
            'width' => '2',
            'marker' => ['visible' => true, 'width' => 10, 'height' => 10],
            'type' => 'Line'
        ];
    }

    return response()->json($formattedData);
}



// function WeeklyStats() {
//     // Calculate the start and end of the current week (Monday to Sunday)
//     $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
//     $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

//     // Query for the top seller (product with the highest sales revenue)
//     $topSeller = Payment::select('ProductId', DB::raw('SUM(amount) AS total_sales'))
//         ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
//         ->groupBy('ProductId')
//         ->orderByDesc('total_sales')
//         ->first();

//     // Query for the most viewed product
//     $mostViewed = Payment::select('ProductId', DB::raw('COUNT(ProductId) AS total_views'))
//         ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
//         ->groupBy('ProductId')
//         ->orderByDesc('total_views')
//         ->first();

//     // Query for the top engaged product (both high sales revenue and high number of views)
//     $topEngaged = Payment::select('ProductId', DB::raw('SUM(amount) AS total_sales, COUNT(ProductId) AS total_views'))
//         ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
//         ->groupBy('ProductId')
//         ->orderByDesc('total_sales')
//         ->orderByDesc('total_views')
//         ->first();

//     // Format the data
//     $weeklyStats = [
//         [
//             'icon' => 'FiShoppingCart', // Add appropriate icon
//             'amount' => $topSeller ? number_format($topSeller->total_sales) : 'N/A',
//             'title' => 'Top Seller',
//             'desc' => 'Highest Revenue Product',
//             'iconBg' => '#FB9678', // Add appropriate background color
//             'pcColor' => 'green-600', // Add appropriate color
//         ],
//         [
//             'icon' => 'GiSunkenEye', // Add appropriate icon
//             'amount' => $mostViewed ? number_format($mostViewed->total_views) : 'N/A',
//             'title' => 'Most Viewed',
//             'desc' => 'Most Viewed Product',
//             'iconBg' => 'rgb(254, 201, 15)', // Add appropriate background color
//             'pcColor' => 'green-600', // Add appropriate color
//         ],
//         [
//             'icon' => 'BsChatLeft', // Add appropriate icon
//             'amount' => $topEngaged ? number_format($topEngaged->total_views) : 'N/A',
//             'title' => 'Top Engaged',
//             'desc' => 'Most Engaging Product',
//             'iconBg' => '#00C292', // Add appropriate background color
//             'pcColor' => 'green-600', // Add appropriate color
//         ],
//     ];

//     return response()->json(['weeklyStats' => $weeklyStats]);
// }

function WeeklyStats() {
    // Calculate the start and end of the current week (Monday to Sunday)
    $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

    // Query for the top seller (product with the highest sales revenue)
    $topSeller = Payment::join('Orders', 'Payments.OrderId', '=', 'Orders.OrderId')
        ->select('Orders.ProductId', DB::raw('SUM(Payments.AmountPaid) AS total_sales'))
        ->whereBetween('Payments.updated_at', [$startOfWeek, $endOfWeek])
        ->groupBy('Orders.ProductId')
        ->orderByDesc('total_sales')
        ->first();

    // Query for the most viewed product
    $mostViewed = ProductAssessment::select('productId', DB::raw('COUNT(productId) AS total_views'))
        ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
        ->groupBy('productId')
        ->orderByDesc('total_views')
        ->first();

    // Query for the top engaged product (both high sales revenue and high number of views)
    $topEngaged = Payment::join('Orders', 'Payments.OrderId', '=', 'Orders.OrderId')
        ->select('Orders.ProductId', DB::raw('SUM(Payments.AmountPaid) AS total_sales, COUNT(Orders.ProductId) AS total_views'))
        ->whereBetween('Payments.updated_at', [$startOfWeek, $endOfWeek])
        ->groupBy('Orders.ProductId')
        ->orderByDesc('total_sales')
        ->orderByDesc('total_views')
        ->first();

    // Format the data
    $weeklyStats = [
        [
            'icon' => 'FiShoppingCart', // Add appropriate icon
            'amount' => $topSeller ? "₵ ".number_format($topSeller->total_sales) : 'N/A',
            'title' => 'Top Seller',
            'desc' => 'Highest Revenue Product',
            'iconBg' => '#FB9678', // Add appropriate background color
            'pcColor' => 'green-600', // Add appropriate color
        ],
        [
            'icon' => 'GiSunkenEye', // Add appropriate icon
            'amount' => $mostViewed ? number_format($mostViewed->total_views) : 'N/A',
            'title' => 'Most Viewed',
            'desc' => 'Most Viewed Product',
            'iconBg' => 'rgb(254, 201, 15)', // Add appropriate background color
            'pcColor' => 'green-600', // Add appropriate color
        ],
        [
            'icon' => 'BsChatLeft', // Add appropriate icon
            'amount' => $topEngaged ? number_format($topEngaged->total_views) : 'N/A',
            'title' => 'Top Engaged',
            'desc' => 'Most Engaging Product',
            'iconBg' => '#00C292', // Add appropriate background color
            'pcColor' => 'green-600', // Add appropriate color
        ],
    ];

    return response()->json(['weeklyStats' => $weeklyStats]);
}


function TopCustomers() {
    // Query to fetch the top 3 customers with the highest amount paid in the Payment table
    $topCustomers = Payment::select('UserId', DB::raw('SUM(AmountPaid) AS total_amount'))
        ->groupBy('UserId')
        ->orderByDesc('total_amount')
        ->limit(5)
        ->get();

    // Initialize an array to store the portfolio stats data
    $portfolioStats = [];

    // Loop through the top customers
    foreach ($topCustomers as $customer) {
        // Fetch customer details from the Customer table based on CustomerId
        $customerDetails = Customer::select('UserId', 'Username')
            ->where('UserId', $customer->UserId)
            ->first();

        // Check if customer details are found
        if ($customerDetails) {
            // Push customer details to the portfolio stats array
            $portfolioStats[] = [
                'amount' => number_format($customer->total_amount, 2), // Format amount
                'title' => $customerDetails->Username, // Assuming Name is the column name for the customer's name
                'userId' => $customerDetails->UserId,
                'pcColor' => 'green-600', // Add appropriate color
            ];
        }
    }

    // Return portfolio stats data
    return response()->json(['topCustomers' => $portfolioStats]);
}

// function TopTrendingPortfolio() {
//     // Query to fetch the top 5 customers with the highest amount paid in the Payment table
//     $topCustomers = Payment::select('ProductId', DB::raw('SUM(amount) AS total_amount'))
//         ->groupBy('ProductId')
//         ->orderByDesc('total_amount')
//         ->limit(5)
//         ->get();

//     // Initialize an array to store the portfolio stats data
//     $portfolioStats = [];

//     // Loop through the top customers
//     foreach ($topCustomers as $customer) {
//         // Fetch project details from the OurPortfolioProjects table based on ProductId
//         $projectDetails = OurPortfolioProjects::select('ProjectId', 'Picture', 'ProjectName')
//             ->where('ProjectId', $customer->ProductId)
//             ->first();

//         // Check if project details are found
//         if ($projectDetails) {
//             // Push project details to the portfolio stats array
//             $portfolioStats[] = [
//                 'img' => $projectDetails->Picture, // Assuming Picture is the column name for the project's image
//                 'amount' => number_format($customer->total_amount, 2), // Format amount
//                 'title' => $projectDetails->ProjectName, // Assuming ProjectName is the column name for the project's name
//                 'pcColor' => 'green-600', // Add appropriate color
//             ];
//         }
//     }

//     // Return portfolio stats data
//     return response()->json(['PortfolioStats' => $portfolioStats]);
// }

function TopTrendingPortfolio() {
    // Get the current month start and end dates
    $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
    $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

    // Query to fetch the top 5 most popular products for the current month
    // based on the amount paid and where the payment status is 'confirmed'
    $topProducts = Order::join('Payments', 'Orders.OrderId', '=', 'Payments.OrderId')
        ->select('Orders.ProductId', DB::raw('SUM(Payments.AmountPaid) AS total_amount'))
        ->whereBetween('Payments.updated_at', [$startOfMonth, $endOfMonth])
        ->where('Payments.Status', 'confirmed')
        ->groupBy('Orders.ProductId')
        ->orderByDesc('total_amount')
        ->limit(5)
        ->get();

    // Initialize an array to store the portfolio stats data
    $portfolioStats = [];

    // Loop through the top products
    foreach ($topProducts as $product) {
        // Fetch the product name and other details if needed from the Order table or related source
        // Since we are only using Order and Payment tables, this part might involve additional assumptions
        // For simplicity, we're assuming that we need to provide the ProductId and total_amount

        // Push product details to the portfolio stats array
        $portfolioStats[] = [
            'amount' => number_format($product->total_amount, 2), // Format amount
            'title' => 'Product ID: ' . $product->ProductId, // Placeholder title
            'pcColor' => 'green-600', // Add appropriate color
        ];
    }

    // Return portfolio stats data
    return response()->json(['PortfolioStats' => $portfolioStats]);
}



function Auditing() {
    // Query to fetch audit trial records ordered by created_at in descending order
    $auditTrials = AuditTrial::orderByDesc('created_at')->get();

    // Return the audit trial records
    return response()->json(['auditTrials' => $auditTrials]);
}

function GetVisitors(){
    $v = Visitors::get();
    return response()->json(["visitors"=>$v],200);
}

function CountVisitors(){
    $v = Visitors::Count();
    return response()->json(["visitors"=>$v],200);
}

function CountCountryVisitors() {
    // Get today's and yesterday's date
    $today = Carbon::today()->toDateString();
    $yesterday = Carbon::yesterday()->toDateString();

    // Count today's records for AuditTrial
    $todayAuditTrailCount = AuditTrial::whereDate('created_at', $today)->count();

    // Count yesterday's records for AuditTrail
    $yesterdayAuditTrailCount = AuditTrial::whereDate('created_at', $yesterday)->count();

    // Calculate the percentage change for AuditTrail
    $auditTrailPercentageChange = $yesterdayAuditTrailCount > 0
        ? (($todayAuditTrailCount - $yesterdayAuditTrailCount) / $yesterdayAuditTrailCount) * 100
        : ($todayAuditTrailCount > 0 ? 100 : 0);

    // Count today's records for CustomerTrail
    $todayCustomerTrailCount = CustomerTrail::whereDate('created_at', $today)->count();

    // Count yesterday's records for CustomerTrail
    $yesterdayCustomerTrailCount = CustomerTrail::whereDate('created_at', $yesterday)->count();

    // Calculate the percentage change for CustomerTrail
    $customerTrailPercentageChange = $yesterdayCustomerTrailCount > 0
        ? (($todayCustomerTrailCount - $yesterdayCustomerTrailCount) / $yesterdayCustomerTrailCount) * 100
        : ($todayCustomerTrailCount > 0 ? 100 : 0);

    // Count today's records for ProductAssessment
    $todayProductAssessmentCount = ProductAssessment::whereDate('created_at', $today)->count();

    // Count yesterday's records for ProductAssessment
    $yesterdayProductAssessmentCount = ProductAssessment::whereDate('created_at', $yesterday)->count();

    // Calculate the percentage change for ProductAssessment
    $productAssessmentPercentageChange = $yesterdayProductAssessmentCount > 0
        ? (($todayProductAssessmentCount - $yesterdayProductAssessmentCount) / $yesterdayProductAssessmentCount) * 100
        : ($todayProductAssessmentCount > 0 ? 100 : 0);

    // Count today's records for RateLimitCatcher
    $todayRateLimitCatcherCount = RateLimitCatcher::whereDate('created_at', $today)->count();

    // Count yesterday's records for RateLimitCatcher
    $yesterdayRateLimitCatcherCount = RateLimitCatcher::whereDate('created_at', $yesterday)->count();

    // Calculate the percentage change for RateLimitCatcher
    $rateLimitCatcherPercentageChange = $yesterdayRateLimitCatcherCount > 0
        ? (($todayRateLimitCatcherCount - $yesterdayRateLimitCatcherCount) / $yesterdayRateLimitCatcherCount) * 100
        : ($todayRateLimitCatcherCount > 0 ? 100 : 0);

    // Define the data array
    $data = [
        [
            'icon' => 'GrShieldSecurity',
            'amount' => strval($todayAuditTrailCount),
            'percentage' => $auditTrailPercentageChange >= 0 ? '+' . number_format($auditTrailPercentageChange, 2) . '%' : number_format($auditTrailPercentageChange, 2) . '%',
            'title' => 'Audit Trails Today',
            'iconColor' => '#03C9D7',
            'iconBg' => '#E5FAFB',
            'pcColor' => $auditTrailPercentageChange >= 0 ? 'green-600' : 'red-600',
        ],
        [
            'icon' => 'SiSecurityscorecard',
            'amount' => strval($todayCustomerTrailCount),
            'percentage' => $customerTrailPercentageChange >= 0 ? '+' . number_format($customerTrailPercentageChange, 2) . '%' : number_format($customerTrailPercentageChange, 2) . '%',
            'title' => 'Customer Trails Today',
            'iconColor' => 'rgb(255, 244, 229)',
            'iconBg' => 'rgb(254, 201, 15)',
            'pcColor' => $customerTrailPercentageChange >= 0 ? 'green-600' : 'red-600',
        ],
        [
            'icon' => 'MdOutlineSecurity',
            'amount' => strval($todayProductAssessmentCount),
            'percentage' => $productAssessmentPercentageChange >= 0 ? '+' . number_format($productAssessmentPercentageChange, 2) . '%' : number_format($productAssessmentPercentageChange, 2) . '%',
            'title' => 'Product Assessments Today',
            'iconColor' => 'rgb(228, 106, 118)',
            'iconBg' => 'rgb(255, 244, 229)',
            'pcColor' => $productAssessmentPercentageChange >= 0 ? 'green-600' : 'red-600',
        ],
        [
            'icon' => 'MdOutlineLocalPolice',
            'amount' => strval($todayRateLimitCatcherCount),
            'percentage' => $rateLimitCatcherPercentageChange >= 0 ? '+' . number_format($rateLimitCatcherPercentageChange, 2) . '%' : number_format($rateLimitCatcherPercentageChange, 2) . '%',
            'title' => 'Rate Limit Catcher Today',
            'iconColor' => 'rgb(0, 194, 146)',
            'iconBg' => 'rgb(235, 250, 242)',
            'pcColor' => $rateLimitCatcherPercentageChange >= 0 ? 'green-600' : 'red-600',
        ]
    ];

    return response()->json($data, 200);
}


}
