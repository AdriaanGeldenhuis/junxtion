# Junxtion Testing Plan

## Pre-Deployment Checklist

### 1. Database Setup
- [ ] Run all migrations in order (000_run_all.sql)
- [ ] Verify tables created: users, staff_roles, sessions, otp_codes, menu_categories, menu_items, orders, order_items, payments, notifications, audit_log, settings, promo_codes, specials
- [ ] Create initial admin user
- [ ] Seed test menu data (optional)

### 2. Configuration
- [ ] Copy config.example.php to config.php
- [ ] Update database credentials
- [ ] Set JWT secret (generate: `openssl rand -base64 32`)
- [ ] Configure Yoco API keys (test mode first)
- [ ] Configure FCM service account (optional for dev)
- [ ] Set SMS provider credentials (or use log mode)

### 3. Security
- [ ] Verify .htaccess files are in place
- [ ] Test /private/ directory is inaccessible from web
- [ ] Test /uploads/ blocks PHP execution
- [ ] Verify error pages work (403, 404, 500)

---

## API Testing

### Customer Authentication
```bash
# Request OTP
curl -X POST http://localhost/api/customer/auth/request-otp \
  -H "Content-Type: application/json" \
  -d '{"phone": "0821234567"}'

# Verify OTP (check logs for code in dev mode)
curl -X POST http://localhost/api/customer/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"phone": "0821234567", "otp": "123456"}'
```

### Staff Authentication
```bash
# Login
curl -X POST http://localhost/api/staff/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@junxtion.co.za", "password": "password"}'
```

### Menu
```bash
# Get public menu
curl http://localhost/api/menu

# Get categories (admin)
curl http://localhost/api/admin/menu/categories \
  -H "Authorization: Bearer {token}"
```

### Orders
```bash
# Create order (customer)
curl -X POST http://localhost/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {customer_token}" \
  -d '{
    "order_type": "pickup",
    "items": [{"item_id": 1, "qty": 2}],
    "notes": "Extra napkins"
  }'

# Get orders (customer)
curl http://localhost/api/orders \
  -H "Authorization: Bearer {customer_token}"

# Update order status (admin)
curl -X POST http://localhost/api/admin/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {admin_token}" \
  -d '{"status": "ACCEPTED"}'
```

### Yoco Webhook
```bash
# Simulate webhook (replace with actual signature)
curl -X POST http://localhost/api/webhooks/yoco \
  -H "Content-Type: application/json" \
  -H "webhook-id: whk_123" \
  -H "webhook-timestamp: 1234567890" \
  -H "webhook-signature: v1,xxxxx" \
  -d '{"type": "payment.succeeded", "payload": {...}}'
```

---

## Functional Testing

### Customer App Flow
1. [ ] Open home page - verify specials carousel loads
2. [ ] Browse menu - categories and items display
3. [ ] Search menu - results filter correctly
4. [ ] Add item to cart - with and without modifiers
5. [ ] View cart - items display correctly
6. [ ] Apply promo code - discount applied
7. [ ] Select order type (pickup/delivery)
8. [ ] Sign in via OTP (or check dev mode toast)
9. [ ] Place order - redirects to Yoco
10. [ ] Complete payment - order status updates
11. [ ] Track order - status progress shows
12. [ ] View order history

### Admin Panel Flow
1. [ ] Login with staff credentials
2. [ ] Dashboard shows stats
3. [ ] Orders page - live updates (5s polling)
4. [ ] Accept order - status changes
5. [ ] Start prep - status changes
6. [ ] Mark ready - customer notified
7. [ ] Complete order
8. [ ] Kitchen mode - large tiles, sound alerts
9. [ ] Menu management - add/edit/delete items
10. [ ] Specials management
11. [ ] Promo codes management
12. [ ] Staff management (manager only)
13. [ ] Settings - toggle ordering, business hours
14. [ ] Notifications - send broadcast

### Order Status Flow
```
PENDING_PAYMENT -> PLACED -> ACCEPTED -> IN_PREP -> READY -> COMPLETED
                                                  -> OUT_FOR_DELIVERY -> COMPLETED
Any active status -> CANCELLED (manager only for ACCEPTED+)
```

---

## Payment Testing (Yoco Test Mode)

### Test Cards
- **Success**: 4111 1111 1111 1111
- **Decline**: 4000 0000 0000 0002
- **3D Secure**: 4000 0000 0000 3220

### Webhook Testing
1. Create order in app
2. Complete payment with test card
3. Verify webhook received (check logs)
4. Verify order status updated to PLACED
5. Verify payment record created

### Refund Testing
1. Complete an order
2. Initiate refund from admin
3. Verify refund webhook received
4. Verify payment status updated

---

## Mobile Testing

### Devices to Test
- [ ] iPhone Safari (iOS 15+)
- [ ] Android Chrome (latest)
- [ ] Tablet landscape mode

### PWA Features
- [ ] Add to home screen works
- [ ] App loads offline (cached pages)
- [ ] Service worker registered
- [ ] Manifest loads correctly

### Responsive Design
- [ ] Bottom nav usable on all screens
- [ ] Forms don't zoom on focus (viewport)
- [ ] Images scale properly
- [ ] Modals don't overflow
- [ ] Kitchen mode fills screen

---

## Performance Testing

### Page Load
- Target: < 3 seconds on 3G
- [ ] Home page
- [ ] Menu page
- [ ] Cart page

### API Response
- Target: < 500ms
- [ ] GET /api/menu
- [ ] GET /api/orders (authenticated)
- [ ] POST /api/orders

### Live Updates
- [ ] Orders page polling doesn't degrade
- [ ] Kitchen mode handles 20+ orders

---

## Security Testing

### Authentication
- [ ] OTP expires after 10 minutes
- [ ] JWT tokens expire correctly
- [ ] Invalid tokens rejected
- [ ] Rate limiting works (5 attempts)

### Authorization
- [ ] Customers can't access admin routes
- [ ] Staff can't access other customers' orders
- [ ] Manager-only routes protected

### Input Validation
- [ ] SQL injection prevented (PDO prepared statements)
- [ ] XSS prevented (htmlspecialchars output)
- [ ] Phone number format validated
- [ ] Price tampering prevented (server calculates total)

### Yoco Security
- [ ] Webhook signature verified
- [ ] Timestamp validated (±3 min)
- [ ] Payment status from webhook only (not successUrl)

---

## Error Handling

### API Errors
- [ ] 400 Bad Request - missing/invalid params
- [ ] 401 Unauthorized - no/invalid token
- [ ] 403 Forbidden - insufficient permissions
- [ ] 404 Not Found - resource doesn't exist
- [ ] 429 Too Many Requests - rate limited
- [ ] 500 Internal Error - caught and logged

### User Feedback
- [ ] Toast messages show for actions
- [ ] Loading states displayed
- [ ] Empty states shown appropriately
- [ ] Network errors handled gracefully

---

## Data Integrity

### Order Prices
- [ ] Price snapshot saved at order time
- [ ] Menu price changes don't affect existing orders
- [ ] Totals calculated server-side
- [ ] Refunds reference original payment

### Audit Trail
- [ ] Order status changes logged
- [ ] Admin actions logged
- [ ] Timestamps correct (SAST)
