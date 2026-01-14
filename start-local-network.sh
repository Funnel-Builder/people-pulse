#!/bin/bash

# PeoplePulse Local Network Startup Script
echo "🚀 Starting PeoplePulse Attendance System..."
echo ""

# Get local IP
LOCAL_IP=$(hostname -I | awk '{print $1}')
echo "📍 Your Local IP: $LOCAL_IP"
echo ""

# Start Laravel server
echo "🔧 Starting Laravel server on port 8000..."
php artisan serve --host=0.0.0.0 --port=8002 &
LARAVEL_PID=$!

# Wait a moment for Laravel to start
sleep 2

echo ""
echo "✅ PeoplePulse is now running!"
echo ""
echo "📱 Access from devices on your WiFi network:"
echo "   → http://$LOCAL_IP:8002"
echo ""
echo "🔐 Login Credentials:"
echo "   Admin:   admin@peoplepulse.com / password"
echo "   Manager: john.manager@peoplepulse.com / password"
echo "   User:    alice@peoplepulse.com / password"
echo ""
echo "🛑 Press Ctrl+C to stop the server"
echo ""

# Wait for Ctrl+C
trap "echo ''; echo '🛑 Stopping servers...'; kill $LARAVEL_PID 2>/dev/null; exit 0" INT

# Keep script running
wait
