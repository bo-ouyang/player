let mysql = require('mysql');
const config = require('../config/config.js');

class Db {
	/**
	 * 构造函数
	 */
	constructor(option = {}) {
		let dbConf = {};
		if (typeof option == 'object' && Object.keys(option).length > 0) {
			dbConf = {
				database: option.database,
				host    : option.host,
				user    : option.user,
				password: option.password,
				port    : option.port
			};
			this.dbPrefix = option.prefix;
		} else {
			dbConf = {
				database: config.mysql.database,
				host    : config.mysql.host,
				user    : config.mysql.user,
				password: config.mysql.password,
				port    : config.mysql.port
			};
			this.dbPrefix = config.mysql.prefix;
		}

		this.connection = mysql.createConnection(dbConf);
		this.connection.connect();
	}

	/**
	 * where查询
	 */
	where(param) {
		var __where = "1=1";
		if (typeof param == "object") {
			for (var k in param) {
				__where += " and `"+k+"` = '" + param[k] + "'";
			}
		}

		if (typeof param == "string") {
			__where += ' and ' + param;
		}

		this._where = __where;
		return this;
	}

	limit(param) {
		var __limit = '';
		if (typeof param != "undefined" && param != null && param != '') {
			__limit = 'limit 0,' + param;
		}

		this._limit = __limit;
		return this;
	}

	order(param) {
		var __order = '';
		if (typeof param != "undefined" && param != null && param != '') {
			__order = 'order by ' + param;
		}

		this._order = __order;
		return this;
	}

	/**
	 * 字段查询
	 */
	field(param) {
		var __field = [];
		if (!param) {
			this._field = "*";
		} else {
			if (typeof param === "string") {
				param = param.split(",");
			}

			param.forEach(function(v) {
				__field.push("`" + v + "`");
			});

			this._field = __field.join(",");
		}

		return this;
	}

	/**
	 * 指定数据表[去除表前缀的表名]
	 */
	table(tablename) {
		this._table = "`" + this.dbPrefix + tablename + "`";
		return this;
	}

	/**
	 * 获取最后sql语句
	 */
	getLastSql() {
		return this.lastSql;
	}

	/**
	 * 开始事务
	 */
	startTrans(callback) {
		this.connection.beginTransaction(function(err) {
			if (err) {
				throw err;
			}

			if (callback) {
				callback();
			}
		});
	}

	/**
	 * 事务回滚
	 */
	rollback() {
		this.connection.rollback();
	}

	/**
	 * 链接断开
	 */
	end() {
		this.connection.end();
	}

	/**
	 * 事务提交
	 */
	commit() {
		this.connection.commit();
	}

	/**
	 * 添加数据
	 */
	add(param, callback) {
		var fields = [];
		var values = [];
		for (var k in param) {
			fields.push("`" + k + "`");
			values.push("'" + param[k] + "'");
		}

		fields = fields.join(",");
		values = values.join(",");
		var sql = "insert into " + this.buildTable() + " (" + fields + ") values(" + values + ")";
		this.lastSql = sql;

		this.connection.query(sql,function(err, result) {
			var res = null;
			if (err) {
				console.log(err.message);
			} else {
				res = result.insertId;
			}

			if (callback) {
				callback(res);
			}
		});
	}

	/**
	 * 批量插入数据
	 */
	addAll(params,callback) {
		let _fields = []
		for (let k in params[0]) {
			_fields.push("`" + k + "`");
		}

		let fields = _fields.join(",");

		let values = [];
		params.forEach(param => {
			let value = []
			for (let k in param) {
				value.push(`'${param[k]}'`);
			}

			value = "(" + value.join(",") +")";
			values.push(value);
		});

		values = values.join(",");
		let sql = `insert into ${this.buildTable()} (${fields}) values ${values}`;
		this.lastSql = sql;
		this.connection.query(sql,function(err, result) {
			var res = null;
			if (err) {
				console.log(err.message);
			} else {
				res = result.affectedRows;
			}

			if (callback) {
				callback(err, res);
			}
		});
	}

	/**
	 * 查询数据
	 */
	find(callback) {
		var sql = "select " + this.buildField() + " from " + this.buildTable() + " where " + this.buildWhere() + " " + this.buildOrder() + " limit 0,1";
		this.lastSql = sql;
		this.connection.query(sql,function(err, result) {
			var res = null;
			if (err) {
				console.log(err.message);
			} else {
				res = result[0] || null;
			}

			if (callback) {
				callback(res);
			}
		});
	}

	/**
	 * 查询多条数据
	 */
	select(callback) {
		var sql = "select " + this.buildField() + " from " + this.buildTable() + " where " + this.buildWhere() + " " + this.buildOrder() + " " + this.buildLimit();
		this.lastSql = sql;
		this.connection.query(sql,function(err, result) {
			var res = [];
			if (err) {
				console.log(err.message);
			} else {
				res = result;
			}

			if (callback) {
				callback(res);
			}
		});
	}

	/**
	 * 修改数据
	 */
	update(data, callback) {
		var updateData = [];
		for (var k in data) {
			updateData.push("`"+k+"`=" + "'" + data[k] + "'");
		}

		updateData = updateData.join(",");
		var t = this.buildTable();
		var w = this.buildWhere();
		var limit = this.buildLimit();

		var sql = `update ${t} set ${updateData} where ${w} ${limit}`;
		this.lastSql = sql;
		this.connection.query(sql,function(err, result) {
			var req = null;
			if (err) {
				console.log(err.message);
			} else {
				req = result.affectedRows;
			}

			if (callback) {
				callback(req);
			}
		});
	}

	/**
	 * 自增
	 */
	incr(data, callback) {
		let updateData = [];
		for (let k in data) {
			updateData.push(`\`${k}\`=\`${k}\` + ${data[k]}`);
		}

		updateData = updateData.join(",");
		var t = this.buildTable();
		var w = this.buildWhere();
		var sql = `update ${t} set ${updateData} where ${w}`;

		this.lastSql = sql;
		this.connection.query(sql, function(err, result) {
			var req = null;
			if (err) {
				console.log(err.message);
			} else {
				req = result.affectedRows;
			}

			if (callback) {
				callback(req);
			}
		});
	}

	/**
	 * 删除
	 */
	delete(callback) {
		var t = this.buildTable();
		var w = this.buildWhere();
		var limit = this.buildLimit();

		var sql = `delete from ${t} where ${w} ${limit}`;
		this.connection.query(sql,function(err, result) {
			var req = null;
			if (err) {
				console.log(err.message);
			} else {
				req = result.affectedRows;
			}

			if (callback) {
				callback(req);
			}
		});
	}

	/**
	 * 获取绑定字段
	 */
	buildField() {
		return this._field || "*";
	}

	/**
	 * 获取绑定条件
	 */
	buildWhere() {
		return this._where || "1=1";
	}

	/**
	 * 获取limit
	 */
	buildLimit() {
		return this._limit || '';
	}

	/**
	 * 绑定表名
	 */
	buildTable() {
		return this._table || "";
	}

	/**
	 * 获取order
	 */
	buildOrder() {
		return this._order || '';
	}
}

module.exports = Db;
