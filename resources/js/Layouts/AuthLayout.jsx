import React from 'react';
import { Outlet } from 'react-router-dom';

const AuthLayout = () => {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-gray-900">Invoice System</h1>
          <p className="text-gray-500">Manage your invoices easily</p>
        </div>
        <Outlet />
      </div>
    </div>
  );
};

export default AuthLayout;