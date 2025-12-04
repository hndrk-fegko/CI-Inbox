#!/bin/bash
# User Profile API Tests

BASE_URL="http://ci-inbox.local/api/user"

echo "=== User Profile API Tests ==="
echo ""

echo "1. GET Profile"
curl -s -X GET "$BASE_URL/profile" | jq .
echo ""

echo "2. UPDATE Profile (Name + Timezone)"
curl -s -X PUT "$BASE_URL/profile" \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice Schmidt","timezone":"Europe/Berlin"}' | jq .
echo ""

echo "3. GET Profile (nach Update)"
curl -s -X GET "$BASE_URL/profile" | jq .
echo ""

echo "4. CHANGE Password (falsches aktuelles Passwort)"
curl -s -X POST "$BASE_URL/profile/change-password" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"wrong","new_password":"newpass123"}' | jq .
echo ""

echo "5. CHANGE Password (zu kurz)"
curl -s -X POST "$BASE_URL/profile/change-password" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"test1234","new_password":"123"}' | jq .
echo ""

echo "6. DELETE Avatar (kein Avatar vorhanden)"
curl -s -X DELETE "$BASE_URL/profile/avatar" | jq .
echo ""

echo "=== Tests Complete ==="
