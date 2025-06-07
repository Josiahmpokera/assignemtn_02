/**
 * Format currency amount according to system settings
 * @param float $amount The amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    global $conn;
    
    // Get currency settings
    $settings = [];
    $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get settings with defaults
    $symbol = $settings['currency_symbol'] ?? 'TZS';
    $position = $settings['currency_position'] ?? 'before';
    $decimals = (int)($settings['decimal_places'] ?? 2);
    
    // Format the number
    $formatted_amount = number_format($amount, $decimals);
    
    // Add currency symbol
    return $position === 'before' 
        ? $symbol . ' ' . $formatted_amount 
        : $formatted_amount . ' ' . $symbol;
} 