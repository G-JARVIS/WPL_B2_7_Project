// Example: Fetch wallets for logged-in user
function getWallets() {
    fetch('wallet.php')
      .then(response => response.json())
      .then(data => {
        console.log('Wallets:', data);
        // Update your wallet UI accordingly
      })
      .catch(error => console.error('Error:', error));
  }
  
  // Example: Create a new wallet
  function createWallet(walletName, initialBalance) {
    fetch('wallet.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ wallet_name: walletName, balance: initialBalance })
    })
    .then(response => response.json())
    .then(data => {
      console.log(data.message);
      // Refresh wallet list
      getWallets();
    })
    .catch(error => console.error('Error:', error));
  }
      