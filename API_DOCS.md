# API Documentation

## Base URL
```
http://yourdomain.com/api/
```

## Authentication

All API endpoints (except `/login`) require authentication using a Bearer token.

### Getting a Token
```http
POST /api/login
Content-Type: application/json

{
    "username": "admin",
    "password": "Password@123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "abc123def456...",
        "user": {
            "id": 1,
            "username": "admin"
        }
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### Using the Token
```http
Authorization: Bearer abc123def456...
```

## Endpoints

### Products

#### Get All Products
```http
GET /api/products?page=1&limit=20&search=keyword
```

**Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (max: 100, default: 20)
- `search` (optional): Search term for product name or code

**Response:**
```json
{
    "success": true,
    "data": {
        "products": [
            {
                "product_id": 1,
                "product_name": "Sample Product",
                "product_code": "PRD202401010001",
                "rate": "99.99",
                "quantity": 100,
                "brand_id": 1,
                "categories_id": 1,
                "status": 1,
                "brand_name": "Sample Brand",
                "categories_name": "Sample Category"
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 20,
            "total": 50,
            "pages": 3
        }
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

#### Get Single Product
```http
GET /api/products?id=1
```

**Response:**
```json
{
    "success": true,
    "data": {
        "product_id": 1,
        "product_name": "Sample Product",
        "product_code": "PRD202401010001",
        "rate": "99.99",
        "quantity": 100,
        "brand_id": 1,
        "categories_id": 1,
        "status": 1,
        "brand_name": "Sample Brand",
        "categories_name": "Sample Category"
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

#### Create Product
```http
POST /api/products
Content-Type: application/json

{
    "product_name": "New Product",
    "product_code": "PRD202401010002",
    "rate": 149.99,
    "quantity": 50,
    "brand_id": 1,
    "categories_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "product_id": 2
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

#### Update Product
```http
PUT /api/products
Content-Type: application/json

{
    "id": 1,
    "product_name": "Updated Product",
    "rate": 199.99,
    "quantity": 75,
    "brand_id": 2,
    "categories_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "updated": true
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

#### Delete Product
```http
DELETE /api/products?id=1
```

**Response:**
```json
{
    "success": true,
    "data": {
        "deleted": true
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### Orders

#### Get All Orders
```http
GET /api/orders?page=1&limit=20
```

**Response:**
```json
{
    "success": true,
    "data": {
        "orders": [
            {
                "order_id": 1,
                "user_id": 1,
                "grand_total": "199.99",
                "paid": "199.99",
                "order_status": 1,
                "order_date": "2024-01-01 12:00:00",
                "username": "admin"
            }
        ]
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

#### Create Order
```http
POST /api/orders
Content-Type: application/json

{
    "user_id": 1,
    "grand_total": 299.99,
    "paid": 299.99
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "order_id": 2
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### Dashboard

#### Get Dashboard Statistics
```http
GET /api/dashboard
```

**Response:**
```json
{
    "success": true,
    "data": {
        "products": 25,
        "orders": 150,
        "revenue": 15000.00,
        "low_stock": 3
    },
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

## Error Responses

All error responses follow this format:

```json
{
    "success": false,
    "error": "Error message",
    "data": null,
    "timestamp": "2024-01-01T12:00:00+00:00"
}
```

### Common HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Rate Limiting

Currently, there is no rate limiting implemented. Consider implementing rate limiting for production use.

## CORS

The API supports cross-origin requests. Configure allowed origins in the API headers as needed.

## SDK Examples

### JavaScript (Fetch API)
```javascript
// Login
const loginResponse = await fetch('/api/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        username: 'admin',
        password: 'Password@123'
    })
});
const loginData = await loginResponse.json();
const token = loginData.data.token;

// Get products
const productsResponse = await fetch('/api/products', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
const productsData = await productsResponse.json();
```

### Python (requests)
```python
import requests

# Login
login_response = requests.post('/api/login', json={
    'username': 'admin',
    'password': 'Password@123'
})
token = login_response.json()['data']['token']

# Get products
headers = {'Authorization': f'Bearer {token}'}
products_response = requests.get('/api/products', headers=headers)
products = products_response.json()['data']['products']
```

### PHP (cURL)
```php
// Login
$ch = curl_init('/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'admin',
    'password' => 'Password@123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
$loginData = json_decode(curl_exec($ch), true);
$token = $loginData['data']['token'];
curl_close($ch);

// Get products
$ch = curl_init('/api/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$productsData = json_decode(curl_exec($ch), true);
$products = $productsData['data']['products'];
curl_close($ch);
```
