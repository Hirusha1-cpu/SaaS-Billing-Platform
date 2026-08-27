import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../../Components/Common/Card';
import { Button } from '../../Components/Common/Button';
import { InvoiceTable } from '../../Components/Invoices/InvoiceTable';
import { InvoiceFilters } from '../../Components/Invoices/InvoiceFilters';
import { Spinner } from '../../Components/Common/Spinner';
import api from '../../Utils/api';
import { PlusIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

const InvoiceIndex = () => {
  const navigate = useNavigate();
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState(null);
  const [filters, setFilters] = useState({});

  useEffect(() => {
    fetchInvoices();
  }, []);

  const fetchInvoices = async (params = {}) => {
    setLoading(true);
    try {
      const response = await api.get('/invoices', { params });
      setInvoices(response.data.data);
      setPagination(response.data);
    } catch (error) {
      toast.error('Failed to fetch invoices');
    } finally {
      setLoading(false);
    }
  };

  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    fetchInvoices(newFilters);
  };

  const handleReset = () => {
    setFilters({});
    fetchInvoices();
  };

  const handleRowClick = (invoice) => {
    navigate(`/invoices/${invoice.id}`);
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Invoices</h1>
        <Button onClick={() => navigate('/invoices/create')}>
          <PlusIcon className="w-4 h-4 mr-2" />
          New Invoice
        </Button>
      </div>

      <InvoiceFilters onFilter={handleFilter} onReset={handleReset} />

      <Card>
        {loading ? (
          <div className="flex justify-center items-center py-12">
            <Spinner size="lg" />
          </div>
        ) : (
          <>
            <InvoiceTable invoices={invoices} onRowClick={handleRowClick} />
            {pagination && (
              <div className="flex justify-between items-center mt-4">
                <p className="text-sm text-gray-500">
                  Showing {pagination.from} to {pagination.to} of {pagination.total}
                </p>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!pagination.prev_page_url}
                    onClick={() => fetchInvoices({ ...filters, page: pagination.current_page - 1 })}
                  >
                    Previous
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!pagination.next_page_url}
                    onClick={() => fetchInvoices({ ...filters, page: pagination.current_page + 1 })}
                  >
                    Next
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </Card>
    </div>
  );
};

export default InvoiceIndex;