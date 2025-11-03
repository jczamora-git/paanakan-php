# Notification Dropdown Component - Integration Guide

## Overview

The notification dropdown component is a global notification system that displays pending appointments in a beautiful dropdown panel. It can be included on any page to provide real-time visibility of pending appointments.

## Files

- `components/notification-dropdown.php` - Main notification component (contains PHP, HTML, CSS, and JavaScript)

## Integration

### Step 1: Add to Your Layout

Include the notification component in any layout that needs it (ideally in a master layout or dashboard template):

```php
<?php
// At the top of your page or layout
include '../components/notification-dropdown.php';
?>
```

### Step 2: Ensure Font Awesome is Loaded

The component requires Font Awesome icons. Make sure this is in your `<head>`:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
```

### Step 3: That's It!

The component automatically:
- Fetches pending appointments from the database
- Displays a bell icon with a badge showing the count
- Shows a dropdown with appointment details when clicked
- Handles click-outside to close
- Supports keyboard (ESC to close)

## How It Works

### Notification Bell

- Fixed position in top-right corner
- Shows red badge with count of pending appointments
- Circular design with hover effects
- Smooth animations

### Dropdown Panel

When clicked, displays:
- **Patient Name** - Color-coded in green
- **Case ID** - Unique patient identifier
- **Appointment Type** - Type of appointment (Prenatal, Regular Checkup, etc.)
- **Scheduled Date & Time** - When the appointment is scheduled
- **Contact Number** - Patient's phone number
- **Action Buttons**:
  - View - Link to appointment records
  - Manage - Link to manage appointments page
- **Footer** - Link to view all appointments

### Empty State

When there are no pending appointments, shows:
- Check circle icon
- "No pending appointments" message
- "All caught up!" subtext

## Features

✅ Global positioning (fixed top-right)  
✅ Dropdown with smooth animations  
✅ Real-time data from database  
✅ Fully responsive (mobile-friendly)  
✅ Click-outside to close  
✅ Keyboard support (ESC key)  
✅ Beautiful UI with color-coded elements  
✅ Action buttons for quick access  
✅ Custom scrollbar styling  
✅ No dependencies (pure PHP/HTML/CSS/JS)  

## Customization

### Change Position

Edit the CSS in `components/notification-dropdown.php`:

```css
.notification-dropdown-wrapper {
    /* Top-right (default) */
    top: 20px;
    right: 20px;
    
    /* Or try: */
    /* top: 20px; left: 20px; */ /* Top-left */
    /* bottom: 20px; right: 20px; */ /* Bottom-right */
}
```

### Change Colors

The component uses `#2E8B57` as the primary green color. Search for it in the CSS and replace:

```css
/* Find lines with #2E8B57 and replace with your color */
color: #2E8B57;
background: #2E8B57;
```

### Limit Appointments Shown

Edit the SQL LIMIT in `components/notification-dropdown.php`:

```php
// Currently shows 10 pending appointments
LIMIT 10

// Change to show 20
LIMIT 20
```

### Change Empty State Message

Edit the HTML in the component:

```php
<p class="notification-empty-text">No pending appointments</p>
<p class="notification-empty-subtext">All caught up!</p>
```

## Query Details

The component fetches:
- Appointment ID
- Patient's first and last name
- Patient's contact number
- Scheduled date and time
- Appointment type
- Patient's case ID

All filtered for status = 'Pending' and ordered by scheduled date.

## Integration Examples

### In admin/dashboard.php

```php
<?php
session_start();
// ... existing code ...
?>

<!DOCTYPE html>
<html>
<head>
    <!-- ... head content ... -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Include notification component -->
        <?php include '../components/notification-dropdown.php'; ?>
        
        <!-- Rest of dashboard -->
        <?php include '../sidebar.php'; ?>
        <main class="dashboard-main-content">
            <!-- ... content ... -->
        </main>
    </div>
</body>
</html>
```

### In midwife/dashboard.php

Same as above - just include the component at the top of the dashboard layout.

### In custom templates

Any page that needs notifications can include it:

```php
<?php include '../components/notification-dropdown.php'; ?>
```

## Removed From

The notification functionality has been removed from:
- `admin/breadcrumb.php` - Cleaned up to focus on breadcrumbs only

## Benefits of This Approach

1. **Separation of Concerns** - Breadcrumbs and notifications are now separate
2. **Reusability** - Can be used on any page
3. **Maintainability** - Single source of truth for notification UI
4. **Better UX** - Dropdown instead of redirect
5. **Responsive** - Works great on mobile
6. **Accessibility** - Keyboard support, semantic HTML

## Troubleshooting

### Icons not showing?
- Verify Font Awesome CSS is loaded in the page

### Dropdown not appearing?
- Check browser console for JavaScript errors
- Ensure jQuery/Bootstrap aren't conflicting
- Check z-index in page CSS

### Appointments not loading?
- Verify database connection is working
- Check that appointments table exists and has Pending status records
- Check database user permissions

### Styling looks off?
- Make sure no other CSS is overriding the notification styles
- Check for CSS conflicts with Bootstrap or other frameworks
- Clear browser cache

## Performance Notes

- Database query is optimized with proper JOINs
- Fetches only necessary columns
- Limits results to 10 by default
- No pagination overhead
- Lightweight JavaScript with minimal DOM manipulation

## Security

- All user input is escaped using `htmlspecialchars()`
- Safe for displaying patient information
- No XSS vulnerabilities
- Respects existing authentication (included in page context)

## Future Enhancements

Possible improvements:
- Auto-refresh dropdown every 30 seconds
- Sound/browser notification for new appointments
- Filter by appointment type
- Search within notifications
- Mark as read/unread
- Real-time updates with WebSocket/Pusher
- Different notification types (alerts, warnings, info)

## Support

For issues or questions, check:
1. Browser console for JavaScript errors
2. Network tab for failed database queries
3. Verify database connection and permissions
4. Check Font Awesome is loaded
