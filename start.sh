#!/bin/bash
# FamilyUs - Start both servers

echo "🏡 Iniciando FamilyUs..."

# Backend
cd backend
php artisan serve --port=8000 &
BACKEND_PID=$!
echo "✅ Backend Laravel rodando em http://localhost:8000"

# Frontend
cd ../frontend
npm run dev &
FRONTEND_PID=$!
echo "✅ Frontend Vue rodando em http://localhost:5173"

echo ""
echo "🚀 App pronto! Acesse: http://localhost:5173"
echo "   Para parar: Ctrl+C"

trap "kill $BACKEND_PID $FRONTEND_PID" EXIT
wait
