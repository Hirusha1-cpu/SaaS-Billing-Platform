import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from '../Components/Common/Sidebar';
import Navbar from '../Components/Common/Navbar';

const MainLayout = () => {
  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Navbar />
        <main className="flex-1 overflow-y-auto p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default MainLayout;