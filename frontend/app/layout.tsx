import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { Providers } from './providers'; // 1. Importe o provedor

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'Chat Request',
  description: 'Sistema de gerenciamento de requerimentos',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="pt-br">
      <body className={inter.className}>
        {/* 2. Use o <Providers> para "envolver" a aplicação */}
        <Providers>
          {children}
        </Providers>
      </body>
    </html>
  );
} 