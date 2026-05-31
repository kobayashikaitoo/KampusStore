-- Migration: Add WhatsApp Number field to users table
-- Run this query jika database sudah ada

ALTER TABLE users ADD COLUMN whatsapp_number VARCHAR(20) NULL DEFAULT NULL;
