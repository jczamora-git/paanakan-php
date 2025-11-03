# Toast Alert Component - Usage Guide

## Overview
A global, reusable toast notification system for displaying success, error, warning, and info messages across your application.

## Files

- `js/toast-alert.js` - Main toast component (required)
- `css/toast-alert.css` - Toast styling (required)
- `js/toast-integration.js` - Integration helper (optional, for automatic PHP session conversion)

## Installation

### Step 1: Include in your HTML head

```html
<!-- In the <head> section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/toast-alert.css">

<!-- At the end of <body> -->
<script src="../js/toast-alert.js"></script>
<script src="../js/toast-integration.js"></script>
```

## Usage Methods

### Method 1: Direct JavaScript Calls (Recommended)

```javascript
// Success notification
Toast.success('Operation completed successfully!');

// Error notification
Toast.error('An error occurred!');

// Warning notification
Toast.warning('Please be careful!');

// Info notification
Toast.info('Here is some information');

// Custom duration (milliseconds)
Toast.success('Quick notification', 1000);
Toast.error('Error message', 5000);
```

### Method 2: Using PHP Session Integration

In your PHP file (after form submission):

```php
<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Your logic here
    $_SESSION['toast_message'] = "Room added successfully!";
    $_SESSION['toast_type'] = "success"; // success, error, warning, info
    header("Location: manage_rooms.php");
    exit();
}
?>
```

In your HTML body tag:

```html
<body 
    <?php if (isset($_SESSION['toast_message'])): ?>
        data-toast-type="<?= $_SESSION['toast_type'] ?>"
        data-toast-message="<?= htmlspecialchars($_SESSION['toast_message']) ?>"
        <?php unset($_SESSION['toast_message']); unset($_SESSION['toast_type']); ?>
    <?php endif; ?>
>
```

### Method 3: Using Helper Function

```javascript
// Same as direct calls but with helper function
showToast('success', 'Operation completed!');
showToast('error', 'An error occurred!');
showToast('warning', 'Please be careful!');
showToast('info', 'Here is some information');

// With custom duration
showToast('success', 'Quick notification', 1000);
```

## Toast Types & Defaults

| Type | Default Duration | Color | Icon |
|------|------------------|-------|------|
| success | 3000ms | Green | Check Circle |
| error | 5000ms | Red | Exclamation Circle |
| warning | 4000ms | Yellow | Exclamation Triangle |
| info | 3000ms | Blue | Info Circle |

## Migration from Bootstrap Alerts

### Before (Bootstrap Alert)
```html
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>
```

### After (Toast Component)
```php
<?php
// In PHP, set session variables
$_SESSION['toast_message'] = "Operation successful!";
$_SESSION['toast_type'] = "success";
?>

<!-- In HTML body -->
<body 
    <?php if (isset($_SESSION['toast_message'])): ?>
        data-toast-type="<?= $_SESSION['toast_type'] ?>"
        data-toast-message="<?= htmlspecialchars($_SESSION['toast_message']) ?>"
        <?php unset($_SESSION['toast_message']); unset($_SESSION['toast_type']); ?>
    <?php endif; ?>
>
```

## Example Implementation

### manage_rooms.php Update

```php
<?php
// In the form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // ... existing code ...
        
        switch ($_POST['action']) {
            case 'add':
                // ... add logic ...
                $_SESSION['toast_message'] = "Room added successfully!";
                $_SESSION['toast_type'] = "success";
                break;
            
            case 'edit':
                // ... edit logic ...
                $_SESSION['toast_message'] = "Room updated successfully!";
                $_SESSION['toast_type'] = "success";
                break;
            
            case 'delete':
                // ... delete logic ...
                $_SESSION['toast_message'] = "Room marked as Under Maintenance.";
                $_SESSION['toast_type'] = "success";
                break;
        }
        header("Location: manage_rooms.php");
        exit();
    }
}
?>

<!-- In HTML body -->
<body>
    <div class="dashboard-container">
        <!-- Include toast trigger data on body -->
        <?php if (isset($_SESSION['toast_message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Toast.<?= $_SESSION['toast_type'] ?>('<?= htmlspecialchars(addslashes($_SESSION['toast_message'])) ?>');
                });
            </script>
            <?php unset($_SESSION['toast_message']); unset($_SESSION['toast_type']); ?>
        <?php endif; ?>
        
        <!-- rest of page -->
    </div>
</body>
```

## Styling Customization

### Change Toast Position
Edit `css/toast-alert.css`:

```css
.toast-container {
    /* top: 20px; right: 20px; */ /* default - top right */
    bottom: 20px;                  /* bottom right */
    right: 20px;
}

/* Or bottom left */
.toast-container {
    bottom: 20px;
    left: 20px;
    right: auto;
}
```

### Change Colors
Modify the color variables in `css/toast-alert.css`:

```css
.toast-success {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
    border-left: 4px solid #your-color-3;
}
```

## Features

✅ No jQuery dependency  
✅ Lightweight (~5KB combined)  
✅ Responsive design (works on mobile)  
✅ Auto-dismiss with manual close option  
✅ XSS protection (HTML escaping)  
✅ Font Awesome icons (required)  
✅ Smooth animations  
✅ Multiple simultaneous toasts  
✅ Customizable duration  
✅ Easy to integrate  

## Browser Support

- Chrome (all versions)
- Firefox (all versions)
- Safari 10+
- Edge (all versions)
- Mobile browsers

## Accessibility

- Icons with text descriptions
- Proper color contrast
- Keyboard dismissible (close button focusable)
- ARIA-friendly

## Troubleshooting

### Toast not appearing?
1. Ensure `toast-alert.js` and `toast-alert.css` are included
2. Check browser console for errors
3. Verify Font Awesome is loaded

### Icons not showing?
- Make sure Font Awesome CSS is included: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`

### Toast appearing behind other elements?
- Check z-index conflicts in your CSS
- Toast container has `z-index: 9999` by default

## Performance Notes

- Toast component is lightweight and doesn't impact page performance
- Multiple toasts are handled efficiently
- Each toast removes itself from DOM after animation completes
- No memory leaks

## Security

- All user input is escaped using `textContent` to prevent XSS
- Safe for displaying user-generated content
- CSRF tokens can be added separately if needed

## License

Use freely in your project.
