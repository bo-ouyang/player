pragma solidity ^0.4.24;
contract TopPlayer {
    address owner;

    constructor() public {
        owner = msg.sender;
    }

    mapping(address => uint256) public senders;

    // 系统管理员
    mapping(address => bool) admins;

    // 获取智能合约余额
    function getBalance() public view returns (uint256) {
        return address(this).balance;
    }

    // 智能合约转账
    function sendToken(address to, uint256 amount) public payable {
        require(msg.sender == owner || isAdmin(msg.sender) == true);
        to.transfer(amount);
    }

    // 获取某一个地址已发送的ETH数量
    function getSender(address addr) public view returns(uint256) {
        if (senders[addr] > 0) {
            return senders[addr];
        }

        return 0;
    }

    // 提取本金后扣除ETH数量
    function extractToken(address addr, uint256 amount) public payable {
        require(msg.sender == owner || isAdmin(msg.sender) == true);
        if (senders[addr] > 0 && senders[addr] >= amount) {
            addr.transfer(amount);
            senders[addr] -= amount;
        }
    }

    // 判断是否系统管理员
    function isAdmin(address addr) public view returns(bool) {
        return admins[addr] == true ? true : false;
    }

    // 添加系统管理员
    function addAdmin(address addr) public {
        require(msg.sender == owner && addr != owner);
        admins[addr] = true;
    }

    // 删除系统管理员
    function delAdmin(address addr) public {
        require(msg.sender == owner && addr != owner);
        delete admins[addr];
    }

    // 收款
    function() public payable {
        require(owner != msg.sender);

        if (msg.value > 0) {
            uint amount = msg.value;
            address sender = msg.sender;
            // 存入智能合约账户
            // address(this).transfer(amount);
            // 添加发送者
            senders[sender] += amount;
        }
    }
}
